<?php

/* Globally available basic data types */
$s_days = array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");

function resetEvent($eventid, $updateuserstats=true)
{
	global $wpdb;
	if ($updateuserstats == true)
	{
		// FIXME: surely there's a better way
		$query = $wpdb->prepare("UPDATE s_eventusers SET confirmedcount = confirmedcount+1 
				WHERE status = 'Confirmed' AND eventid = %d", $eventid);
		$wpdb->query($query);

		$query = $wpdb->prepare("UPDATE s_eventusers SET unconfirmedcount = unconfirmedcount+1 
				WHERE status = 'Unconfirmed' AND eventid = %d", $eventid);

		$wpdb->query($query);

		$query = $wpdb->prepare("UPDATE s_eventusers SET notcomingcount = notcomingcount+1 
				WHERE status = 'Not Coming' AND eventid = %d", $eventid);
		$wpdb->query($query);
	}

	if ($wpdb->update('s_eventusers', 
			array('status'=>'Unconfirmed'),
			array('eventid'=>$eventid),
			array('%s'), 
			array('%d') ) === false)
	{
		print "ERROR updating! Doh!";
		print $wpdb->last_error;
		print "Last query: ".$wpdb->last_query."<br />";
		die();
	}

	header("Location: /events/?eventid=$eventid");
	die();
}

function printUserCard($userdata, $eventid)
{
?>
	<?php if ($userdata->userid == get_current_user_id() ) 
	{ ?> 
		<div style="float:left; font-size:15pt; transform: rotate(-90deg);margin-top:40px;"><b>YOU</b></div> 
	<?php } ?>
	<div class="userphoto"><?=  userphoto($userdata->userid);?></div>
	<div style="float:left;"><b><?= $userdata->display_name ?></b>
		<div class="button"><?= $userdata->status?>
		<?php if ($userdata->userid == get_current_user_id() ) 
		{ ?> (<a href="?eventid=<?=$eventid?>&toggleuser=<?=$userdata->status?>">change status</a>) <?php } ?></div>
		<div>
			<span rel="tooltip" title="Confirmed"><img src="http://trog.qgl.org/up/1305/confirmed.png"> <?= $userdata->confirmedcount ?></span>
			<span rel="tooltip" title="Unconfirmed"><img src="http://trog.qgl.org/up/1305/unconfirmed.png"> <?= $userdata->unconfirmedcount ?></span>
			<span rel="tooltip" title="Not coming"><img src="http://trog.qgl.org/up/1305/notcoming.png"> <?= $userdata->notcomingcount ?></span>
		</div>
	</div>
<?php
}

function isUserAdmin($userid, $eventid)
{
	global $wpdb;

	$query = $wpdb->prepare("SELECT userid FROM s_eventadmins 
				WHERE userid = %d AND eventid = %d", $userid, $eventid);

	$res = $wpdb->get_row($query);

	if ($res == NULL)
		return false;
	else
		return true;
}
