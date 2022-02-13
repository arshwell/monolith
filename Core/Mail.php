<?php

namespace Arsh\Core;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

use Arsh\Core\Tygh\Mailer;
use Arsh\Core\Folder;
use Arsh\Core\Layout;
use Arsh\Core\Piece;
use Arsh\Core\ENV;
use Arsh\Core\Git;

/**
 * PHP Core class for sending and displaying mail templates.

 * @package Arsh/Core
 * @author Valentin Arșavin <valentin@iscreambrands.ro>
*/
final class Mail {

    static function send (string $template, string $address, string $subject, array $mail = array(), array $files = NULL) {
        $html = call_user_func(function () use ($template, $mail) {
            // NOTE: can use the same params Mail::html() can use
            ob_start();
                // We need realpath because also crons can use this class
                require(Folder::realpath('mails/'. $template .'/mail.php'));
            return ob_get_clean();
        });

        $asset          = ENV::design();
        $pieces         = Piece::used($template); // pieces used inside this mail template
        $pieces_path    = NULL;

        if (ENV::board('dev')) {
            Layout::compileMailSCSS($template, $pieces);
        }

        if ($pieces) {
            sort($pieces);

            $pieces_path = ('.p/'. strtolower(implode('/.p/', $pieces)) .'/');
        }

        $html = substr_replace(
            $html,
            '<style type="text/css">'.
                file_get_contents(Folder::realpath(
                    $asset.'mails/'. $template .'/'. $pieces_path .
                    (Func::closestUp(Session::design(), File::folder($asset .'mails/'. $template .'/'. $pieces_path, array('css'), false, false)) ?: 0) .'.css'
                )) .
            '</style>',
            strpos($html, '[@css@]'),
            7
        );

        require(Folder::realpath("App/Core/Tygh/emogrifier/autoload.php"));

        $cssInliner = CssInliner::fromHtml($html)->inlineCss();
        $domDocument = $cssInliner->getDomDocument();
        HtmlPruner::fromDomDocument($domDocument)
            ->removeElementsWithDisplayNone()
            ->removeRedundantClassesAfterCssInlined($cssInliner);
        CssToAttributeConverter::fromDomDocument($domDocument)
            ->convertCssToVisualAttributes();
        $html = HtmlNormalizer::fromDomDocument($domDocument)->render();

        if (ENV::mail('smtp.active')) {
            $mailer = new Mailer();
            $mailer->IsSMTP();

            $mailer->From     = ENV::mail('from.email');
            $mailer->FromName = ENV::mail('from.name');

            $mailer->Host     = ENV::mail('smtp.data.host');
            $mailer->Sender   = ENV::mail('smtp.data.auth');

            if (ENV::mail('smtp.data.password')) {
                $mailer->Username = ENV::mail('smtp.data.auth');
                $mailer->Password = ENV::mail('smtp.data.password');
                $mailer->SMTPAuth = true;
                $mailer->SMTPAutoTLS = false;
            }

            // $mailer->SMTPDebug = true; // prints entire process steps

            $mailer->ContentType  = "text/html";
            $mailer->Port         = ENV::mail('smtp.data.port');
            $mailer->Mailer       = "smtp";
            $mailer->CharSet      = "utf-8";
            $mailer->Subject      = $subject;
            $mailer->Body         = $html;
            $mailer->AltBody      = self::prepareText($html);

            $mailer->AddCustomHeader("Organization: ArshWell " . Git::tag());

            if ($files) {
                foreach ($files as $file) {
                    $mailer->addAttachment($file);
                }
            }

            $mailer->AddAddress($address);
            $mailer->AddReplyTo(ENV::mail('from.email'), ENV::mail('from.name'));

            return $mailer->Send();
    	}
        else {
            $headers = "From: ".ENV::mail('from.name')." < ".ENV::mail('from.email')." > \r\n";
            $headers .= "MIME-Version: 1.0\n";
            $headers .= "Content-Type: text/html; charset=utf-8\n";

            return mail($address, $subject, $html, $headers);
        }
    }

    static function html (string $template, array $mail = array()) {
        ob_start();
            require(Folder::realpath('mails/'. $template .'/mail.php'));
        $html = ob_get_clean();

        $pieces = Piece::used($template); // pieces used inside this mail template

        if (ENV::board('dev')) {
            Layout::compileMailSCSS($template, $pieces);
        }

        // Supervisors see all resources separately.
        if (ENV::board('dev') && ENV::supervisor()) {
            $asset  = ENV::root().'/'.ENV::design();
            $time   = substr(str_shuffle("BCDFGHKLMNPQRSTVWXYZ"), 0, 4);

            $link = $asset.'dev/'.implode(
                '?v='.$time.'"></link>'.PHP_EOL.'<link type="text/css" rel="stylesheet" href="'.$asset.'dev/',
                array_map(function ($file) {
                    return Folder::shorter($file);
                }, array_column(Layout::mediaMailSCSS($template, $pieces, true)['files'], 'name'))
            ).'?v='.$time;
        }
        else {
            $asset          = ENV::design();
            $pieces_path    = NULL;

            if ($pieces) {
                sort($pieces);

                $pieces_path = ('.p/'. strtolower(implode('/.p/', $pieces)) .'/');
            }

            $link = $asset.'mails/'. $template .'/'. $pieces_path . (Func::closestUp(Session::design(), File::folder($asset .'mails/'. $template .'/'. $pieces_path, array('css'), false, false)) ?: 0) .'.css?v='.filemtime('env.json');
        }

        $html = substr_replace(
            $html,
            '<link rel="stylesheet" type="text/css" href="'. $link .'" />',
            strpos($html, '[@css@]'),
            7
        );

        return '<iframe width="100%" height="100%" frameborder="0" srcdoc="'.htmlentities($html).'"></iframe>';
    }

    private static function prepareText (string $text): string {
        return preg_replace("/\n{3,}/", "\n\n", str_replace("\t", '', str_replace('&nbsp;', '', strip_tags($text))));
    }
}
