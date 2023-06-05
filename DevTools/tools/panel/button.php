<?php

use Arshwell\Monolith\DevTool\DevToolData;
use Arshwell\Monolith\Session;
use Arshwell\Monolith\Piece;
use Arshwell\Monolith\Func;
use Arshwell\Monolith\Web;

$hashed_arsh_version = substr(md5(DevToolData::ArshwellVersion()), 0, 5); ?>

<!-- - - - - - - - - - - - - - - - - - - - Arshwell | DevPanel - - - - - - - - - - - - - - - - - - - -->
<script type="text/javascript">
    'use strict';
    var script = document.currentScript;

    window.onload = function () {
        var body = document.querySelector('html > body');

        <?php // KeyDown DevPanel Password ?>
        function KDwnDvPnlPswrd (event) {
            if (displayed) {
                document.removeEventListener("keydown", KDwnDvPnlPswrd);
            }

            if (event.key == "Enter" || event.key == " ") {

                if (displayed) {
                    script.nextElementSibling.children[0].click(); // open DevPanel Box
                }
                else if (pass.length > 8) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', window.location);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.responseType = 'json';
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            if (('valid' in xhr.response) && xhr.response['valid'] == true) {
                                displayed = true;
                                VanillaJS.fadeIn(script.nextElementSibling, 'flex'); // display DevPanel Button
                            }
                        }
                    };
                    xhr.send('rshwll=<?= $hashed_arsh_version ?>&form_token='+Form.token('form')+'&pass='+pass.toLowerCase()+'&'+Form.token('form')+'&pnl=AJAX/panel.activate');
                }

                pass = ''; // clear
            }
            else {
                pass += event.key;
            }
        }

        <?php // if we are on desktop, we fadeIn the button ?>
        if (body.getAttribute('mobile') == '0' && body.getAttribute('tablet') != 'true') {
            <?php
            // if visible
            if (Session::panel('active')) { ?>
                VanillaJS.fadeIn(script.nextElementSibling, 'flex');
            <?php }
            // we listen for keydown password
            else { ?>
                var pass = '';
                var displayed = false;

                document.addEventListener("keydown", KDwnDvPnlPswrd);
            <?php } ?>
        }

        var button      = script.nextElementSibling;
        var button_text = script.nextElementSibling.children[0];
        var button_icon = script.nextElementSibling.children[1];
        var iframe;

        button_icon.onmousedown = function () {
            var left = -1, top = -1, button_width = button.offsetWidth, button_height = button.offsetHeight;

            button_icon.style.color = "green";

            document.onmouseup = function (e) {
                e = e || window.event;

                // stop moving when mouse button is released:
                document.onmouseup = null;
                document.onmousemove = null;

                if (left > -1 && top > -1) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', window.location);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                    xhr.send('rshwll=<?= $hashed_arsh_version ?>&pnl=AJAX/button.position&tp='+ button.style.top +'&lft=' + button.style.left);
                }

                button_icon.style.color = "white";
            };
            // call a function whenever the cursor moves:
            document.onmousemove = function (e) {
                e = e || window.event;

                // calculate the new cursor position:
                left = e.clientX;
                top = e.clientY;

                // set the element's new position:
                if (button.style.left != '0px' && button.style.left != ((document.body.scrollWidth - button_width) + 'px')) {
                    if (button.offsetTop < (window.innerHeight/2))
                        button.style.top = '0px';
                    else
                        button.style.top = (window.innerHeight - button_height) + 'px';
                }
                else {
                    button.style.top = Math.max(0, Math.min(top, window.innerHeight - button_height)) + 'px';
                }

                if (button.style.top != (0+'px') && button.style.top != ((window.innerHeight - button_height) + 'px')) {
                    if (button.offsetLeft < (document.body.scrollWidth/2))
                        button.style.left = '0px';
                    else
                        button.style.left = (document.body.scrollWidth - button_width) + 'px';
                }
                else {
                    button.style.left = Math.max(0, Math.min(left, document.body.scrollWidth - button_width)) + 'px';
                }
            };
        };

        button_text.onclick = function () {
            button.disabled = true;

            setTimeout(function() {
                button.removeAttribute("disabled");
            }, 750);

            if (iframe) {
                iframe.remove();
                iframe = undefined;

                button_text.style.textShadow = "1px 1px 2px black";
                button_text.innerHTML        = "DevPanel";
            }
            else {
                document.removeEventListener("keydown", KDwnDvPnlPswrd);

                iframe = document.createElement('iframe');

                iframe.style.backgroundColor= "rgba(0, 0, 0, 0.25)";
                iframe.style.zIndex         = "2147483645";
                iframe.style.position       = "fixed";
                iframe.style.top            = "0px";
                iframe.style.bottom         = "0px";
                iframe.style.left           = "0px";
                iframe.style.right          = "0px";
                iframe.style.width          = "100%";
                iframe.style.height         = "100%";
                iframe.style.padding        = "5%";
                iframe.style.backgroundImage= "url(" + location.protocol +'//'+ location.host + location.pathname +'?'+ "<?= http_build_query(array(
                    'rshwll'    => $hashed_arsh_version,
                    'hdr'       => 'image/png',
                    'rsrc'      => 'images/DevPanel/background.png'
                )) ?>)";
                iframe.style.backgroundRepeat = "no-repeat";
                iframe.style.backgroundPosition= "center";
                iframe.style.backgroundSize = "cover";
                iframe.style.border         = "none";
                iframe.style.boxShadow      = "#343a40 0px 0px 50px 10px inset";
                iframe.src = location.protocol +'//'+ location.host + location.pathname +'?'+ "<?= http_build_query(Func::rShuffle(array(
                    'rshwll'    => $hashed_arsh_version,
                    'pnl'       => 'box',
                    'data'      => urlencode(serialize(Func::rShuffle(array(
                        'time'      => time(),
                        'new'       => Session::isNew(),
                        'PHP'       => ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000),
                        'path'      => Web::path(),
                        'route'     => Web::key(),
                        'language'  => Web::language(),
                        'pieces'    => Piece::used(),
                        'compiled'  => $compiled ?? NULL
                    ))))
                ))) ?>";

                document.body.appendChild(iframe);

                button_text.style.textShadow = "1px 1px 2px red";
                button_text.innerHTML        = "DevClose";
            }
        };
    };
</script>
<button type="button" style="display: none !important; cursor: pointer !important; position: fixed !important;
border: 1px solid #000; font-family: serif !important;
top: <?= Session::panel('button.position.top') ?> !important;
bottom: <?= Session::panel('button.position.bottom') ?> !important;
left: <?= Session::panel('button.position.left') ?> !important;
right: <?= Session::panel('button.position.right') ?> !important;
box-shadow: 0 0 5px rgba(0, 0, 0, 0.75) !important; color: white !important; background: none !important;
padding: 0px !important; height: min-content !important; font-size: 13px !important;
letter-spacing: 1px !important; text-shadow: 1px 1px 2px black !important; z-index: 2147483646 !important;">
    <span style="border: 1px solid #000; padding: 2px 7px 2px 7px !important; display: inline-block; background: none !important;
    background-image: radial-gradient(rgb(45, 45, 45), rgb(20, 20, 20), rgb(5, 5, 5)) !important;">
        DevPanel
    </span>
    <span style="border: 1px solid #000; padding: 2px 7px 2px 7px !important; display: inline-block; background: none !important;
    background-image: radial-gradient(rgb(45, 45, 45), rgb(20, 20, 20), rgb(5, 5, 5)) !important;">
        &#9881;
    </span>
</button>
<!-- - - - - - - - - - - - - - - - - - - - Arshwell | DevPanel - - - - - - - - - - - - - - - - - - - -->
