<?php

namespace Arshwell\Monolith;

use Pelago\Emogrifier\CssInliner;
use Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter;
use Pelago\Emogrifier\HtmlProcessor\HtmlNormalizer;
use Pelago\Emogrifier\HtmlProcessor\HtmlPruner;

use Arshwell\Monolith\Folder;
use Arshwell\Monolith\Table;
use Arshwell\Monolith\Layout;
use Arshwell\Monolith\Piece;
use Arshwell\Monolith\StaticHandler;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * PHP Class for sending and displaying mail templates.

 * @package https://github.com/arshwell/monolith
*/
abstract class Mail extends Table {

    abstract function isSmtpActive(): bool;

    abstract function getFromEmail(): string;
    abstract function getFromName(): string;

    abstract function getSmtpDataHost(): string;
    abstract function getSmtpDataPassword(): string;
    abstract function getSmtpDataAuth(): string;
    abstract function getSmtpDataPort(): string;

    function send (string $template, string $address, string $subject, array $mail = array(), array $files = NULL) {
        $html = call_user_func(function () use ($template, $mail) {
            // NOTE: can use the same params Mail::html() can use
            ob_start();
                // We need realpath because also crons can use this class
                require(Folder::realpath('mails/'. $template .'/mail.php'));
            return ob_get_clean();
        });

        $asset          = 'uploads/design/';
        $pieces         = Piece::used($template); // pieces used inside this mail template
        $pieces_path    = NULL;

        Layout::compileMailSCSS($template, $pieces);

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

        $cssInliner = CssInliner::fromHtml($html)->inlineCss();
        $domDocument = $cssInliner->getDomDocument();
        HtmlPruner::fromDomDocument($domDocument)
            ->removeElementsWithDisplayNone()
            ->removeRedundantClassesAfterCssInlined($cssInliner);
        CssToAttributeConverter::fromDomDocument($domDocument)
            ->convertCssToVisualAttributes();
        $html = HtmlNormalizer::fromDomDocument($domDocument)->render();

        if ($this->isSmtpActive()) {
            $mailer = new PHPMailer();
            $mailer->IsSMTP();

            $mailer->From     = $this->getFromEmail();
            $mailer->FromName = $this->getFromName();

            $mailer->Host     = $this->getSmtpDataHost();
            $mailer->Sender   = $this->getSmtpDataAuth();

            if ($this->getSmtpDataPassword()) {
                $mailer->Username = $this->getSmtpDataAuth();
                $mailer->Password = $this->getSmtpDataPassword();
                $mailer->SMTPAuth = true;
                $mailer->SMTPAutoTLS = false;
            }

            // $mailer->SMTPDebug = true; // prints entire process steps

            $mailer->ContentType  = "text/html";
            $mailer->Port         = $this->getSmtpDataPort();
            $mailer->Mailer       = "smtp";
            $mailer->CharSet      = "utf-8";
            $mailer->Subject      = $subject;
            $mailer->Body         = $html;
            $mailer->AltBody      = self::prepareText($html);

            $mailer->AddCustomHeader("Organization: " . $this->getFromName());

            if ($files) {
                foreach ($files as $file) {
                    $mailer->addAttachment($file);
                }
            }

            $mailer->AddAddress($address);
            $mailer->AddReplyTo($this->getFromEmail(), $this->getFromName());

            return $mailer->Send();
    	}
        else {
            $headers = "From: ".$this->getFromName()." < ".$this->getFromEmail()." > \r\n";
            $headers .= "MIME-Version: 1.0\n";
            $headers .= "Content-Type: text/html; charset=utf-8\n";

            return mail($address, $subject, $html, $headers);
        }
    }

    function html (string $template, array $mail = array()) {
        ob_start();
            require(Folder::realpath('mails/'. $template .'/mail.php'));
        $html = ob_get_clean();

        $pieces = Piece::used($template); // pieces used inside this mail template

        if (StaticHandler::getEnvConfig('development.debug')) {
            Layout::compileMailSCSS($template, $pieces);
        }

        // Supervisors see all resources separately.
        if (StaticHandler::getEnvConfig('development.debug') && StaticHandler::supervisor()) {
            $asset  = StaticHandler::getEnvConfig()->getSiteRoot().'/'.'uploads/design/';
            $time   = substr(str_shuffle("BCDFGHKLMNPQRSTVWXYZ"), 0, 4);

            $link = $asset.'dev/'.implode(
                '?v='.$time.'"></link>'.PHP_EOL.'<link type="text/css" rel="stylesheet" href="'.$asset.'dev/',
                array_map(function ($file) {
                    return Folder::shorter($file);
                }, array_column(Layout::mediaMailSCSS($template, $pieces, true)['files'], 'name'))
            ).'?v='.$time;
        }
        else {
            $asset          = 'uploads/design/';
            $pieces_path    = NULL;

            if ($pieces) {
                sort($pieces);

                $pieces_path = ('.p/'. strtolower(implode('/.p/', $pieces)) .'/');
            }

            $link = $asset.'mails/'. $template .'/'. $pieces_path . (Func::closestUp(Session::design(), File::folder($asset .'mails/'. $template .'/'. $pieces_path, array('css'), false, false)) ?: 0) .'.css?v='.getlastmod();
        }

        $html = substr_replace(
            $html,
            '<link rel="stylesheet" type="text/css" href="'. $link .'" />',
            strpos($html, '[@css@]'),
            7
        );

        return '<iframe width="100%" height="100%" frameborder="0" srcdoc="'.htmlentities($html).'"></iframe>';
    }

    private function prepareText (string $text): string {
        return preg_replace("/\n{3,}/", "\n\n", str_replace("\t", '', str_replace('&nbsp;', '', strip_tags($text))));
    }
}
