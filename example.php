<?php
use Deviny\BigBlueButtonPhpApi\Bbb;
include(__DIR__."/src/Bbb.php");
define("DEFAULT_SECURITY_SALT", "abcdefg");
define("DEFAULT_SERVER_BASE_URL", "https://<your-domain>/bigbluebutton/");

$meeting_id = time();
$meeting = new Bbb($meeting_id);
$meeting->setName("This is Meeting Name");
echo sprintf("<a href='%s' >Join as Student</a><br/>",$meeting->attendee("John"));
echo sprintf("<a href='%s' >Join as Teacher</a><br/>",$meeting->moderator("Devin"));
