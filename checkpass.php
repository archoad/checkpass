<?php
/*=========================================================
// File:        checkpass.php
// Description: main file of checkpass
// Created:     2020-03-02
// Licence:     GPL-3.0-or-later
// Copyright 2020 Michel Dubois

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
=========================================================*/


function genCaptcha() {
	if(isset($_SESSION['sess_captcha'])) {
		unset($_SESSION['sess_captcha']);
	}
	$imgWidth = 100;
	$imgHeight = 24;
	$nbrLines = 5;
	$img = imagecreatetruecolor($imgWidth, $imgHeight);
	$bg = imagecolorallocate($img, 0, 0, 0);
	imagecolortransparent($img, $bg);
	for($i=0; $i<=$nbrLines; $i++) {
		$lineColor = imagecolorallocate($img, rand(0,255), rand(0,255), rand(0,255));
		imageline($img, rand(1, $imgWidth-$imgHeight), rand(1, $imgHeight), rand(1, $imgWidth+$imgHeight), rand(1, $imgHeight), $lineColor);
	}
	$captchaNumber = ["un", "deux", "trois", "quatre", "cinq"];
	$val1 = rand(1, 5);
	$val2 = rand(1, 5);
	$_SESSION['sess_captcha'] = $val1 * $val2;
	$captchaString = $captchaNumber[$val1-1].'*'.$captchaNumber[$val2-1];
	$textColor = imagecolorallocate($img, 40, 45, 50);
	imagestring($img, 3, 0, 4, $captchaString, $textColor);
	ob_start();
	imagepng($img);
	$rawImageBytes = ob_get_clean();
	imagedestroy($img);
	return(base64_encode($rawImageBytes));
}


function headPage() {
	header("cache-control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Content-type: text/html; charset=utf-8");
	header('X-Content-Type-Options: "nosniff"');
	header("X-XSS-Protection: 1; mode=block");
	header("X-Frame-Options: deny");
	printf("<!DOCTYPE html><html lang='fr-FR'><head>");
	printf("<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>");
	printf("<title>Test de mots de passe</title>");
	printf("<link href='style.css' rel='StyleSheet' type='text/css' media='all' />");
	printf("</head><body>");
}


function footPage() {
	printf("</body></html>");
}


function testForm() {
	$captcha = genCaptcha();
	printf("<div class='container ctn-l ctn-half'>");
	printf("<span class='oneliner brown'>// Test de votre mot de passe -----------------------------------------------------------------------------------------------------------------------------------------------------------</span><br />");
	printf("<form method='post' id='passwd' action='checkpass.php'>");
	printf("<table><tr><td>");
	printf("<input type='password' size='30' maxlength='30' name='pass' id='pass' placeholder='Saisissez votre mot de passe' autocomplete='current-password' required />");
	printf("</td><td>&nbsp;</td><td>");
	printf("<img src='data:image/png;base64,%s' alt='captcha'/>", $captcha);
	printf("</td><td>&nbsp;</td><td>");
	printf("<input type='text' size='10' maxlength='10' name='captcha' id='captcha' placeholder='Saisir le code' required />");
	printf("</td><td>&nbsp;</td><td>");
	printf("<input type='submit' value='Testez' />");
	printf("</td></tr></table>");
	printf("</form>");
	printf("<span class='oneliner brown'>// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------</span><br /></div>");
}


function validateCaptcha($captcha) {
	if (strncmp($_SESSION['sess_captcha'], $captcha, 6) === 0) {
		return true;
	} else {
		return false;
	}
}


function destroySession() {
	session_unset();
	session_destroy();
	session_write_close();
	setcookie(session_name(),'',0,'/');
	header('Location: checkpass.php');
}


function displayResult($passwd) {
	$passwd = strtoupper(sha1($passwd));
	$range = substr($passwd, 0, 5);
	$url = sprintf("https://api.pwnedpasswords.com/range/%s", $range);
	$result = file_get_contents($url);
	$result = explode("\n", $result);
	$good = true;
	for ($i=0; $i<count($result); $i++) {
		$item = explode(":", $result[$i]);
		$testPass = $range.$item[0];
		if (strcmp($passwd, $testPass) == 0) {
			$msg = sprintf("<span class='orange'>Votre mot de passe est apparu %d fois dans les bases de données volées et accessibles sur Internet</span>", $item[1]);
			$good = false;
		}
	}
	if ($good) {
		$msg = sprintf("<span class='green'>Votre mot de passe n'apparaît pas dans les bases de données volées et accessibles sur Internet</span>");
	}
	printf("<div class='container ctn-r ctn-half'>");
	printf("<span class='oneliner brown'>// Résultat -----------------------------------------------------------------------------------------------------------------------------------------------------------</span><br />");
	printf("%s<br />", $msg);
	printf("<a class='blue' href='https://haveibeenpwned.com/Passwords'>Source</a>");
	printf("<span class='oneliner brown'>// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------</span><br /></div>");
}


session_start();
if (isset($_POST['captcha'])) {
	if (validateCaptcha($_POST['captcha'])) {
		headPage();
		displayResult($_POST['pass']);
		testForm();
		footPage();
	} else {
		destroySession();
	}
} else {
	headPage();
	testForm();
	footPage();
}



?>
