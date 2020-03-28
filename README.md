## BigBlueButton PHP API

Original version available at: https://github.com/petermentzer/bbb-api-php

Re-packed by Devin Yang.

https://yty.ccc.tc/demo/

## Example
Create Meeting and Join Link.
<pre>
&lt;?php

use DevinY\BigBlueButtonPhpApi\Bbb;
require __DIR__.'/../vendor/autoload.php';
define("DEFAULT_SECURITY_SALT", "YOUR SALT");
define("DEFAULT_SERVER_BASE_URL", "YOUR SERVER URL");

$meeting_id = time();
$meeting = new Bbb($meeting_id);
$meeting->setName("This is Meeting Name");
echo sprintf("&lt;a href='%s'&gt;Join as Student&lt;/a&gt;&lt;br/&gt;",$meeting->attendee("John"));
echo sprintf("&lt;a href='%s'&gt;Join as Teacher&lt;/a&gt;&lt;br/&gt;",$meeting->moderator("Devin"));
?&gt;
</pre>

## Preupload slides

<pre>
&lt;?php
$meetingObj->slides(['https://ccc.test/p1.pdf','https://ccc.test/p2.pdf']);
?&gt;
</pre>

## Define Different Server
<pre>
&lt;?php
$meeting = new Bbb("test");
$meeting->setServerBaseUrl('YOUR SERVER URL');
$meeting->setSecret('YOUR SALT');
?&gt;
</pre>

## SetClinetPage by default is BigBlueButton.html
<pre>
&lt;?php
$meetingObj->setClientPage("ccc.html");
?&gt;
</pre>
## More Available methods
<pre>
$meeting->setWelcome('Welecome message for all')
    ->setModeratorOnlyMessage('Only teacher can see this messsage');
$meeting->setDuration(20);
$meeting->setRecord("true"); //Old version
$meeting->setAutoStartRecording(false);
$meeting->setAllowStartStopRecording(true);
$meeting->setLogoutUrl('https://YourDomain');
</pre>
