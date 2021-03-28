<script type="text/javascript">
    $(function () {
        $("[rel='tooltip']").tooltip();
    });
</script>

<style>
.userphoto
{
	float:left;
	margin-right:10px;
	width:96px;
	height:96px;
}

.username
{
	font-weight:bold;
}

.userrow
{
	width:400px;
	height:96px;
	border: solid 1px #cccccc;
	margin-bottom:12px;
}

.alt
{
	background-color:#f7f7f7;
}

.my
{
	box-shadow: 5px 5px 5px #888888;
}

.green
{
	background-color:#D4EBC2;
}

.red
{
	background-color:#F0D1DC;
}


.eventinfo
{
	height:96px;
	background-color: #f8f8f8;
	margin-bottom:10px;
	border: solid 1px #cccccc;
}

.button
{
	background-color: #FFFFFF;
	background-image: -moz-linear-gradient(-90deg, #FAFAFA, #ECECEC);
	border: 1px solid #999999;
	border-radius: 3px 3px 3px 3px;
	box-shadow: 2px 2px 0 rgba(0, 0, 0, 0.05);
	color: #000000;
	font-family: Tahoma;
	font-size: 14px;
	margin: 0 4px 4px 0;
	padding: 2px 3px 3px;
	text-decoration: none;
	text-shadow: 0 0 2px #FFFFFF;
	text-transform: uppercase;
}

</style>

<?php while (have_posts()) : the_post(); ?>
  <?php the_content(); ?>
  <?php wp_link_pages(array('before' => '<nav class="pagination">', 'after' => '</nav>')); ?>
<?php endwhile; ?>

<?php

$userid = get_current_user_id();

if ($userid == 0)
{
	print "Please login first.";
	wp_login_form();
	die();
}


if (isset($_GET['eventid']) && is_numeric($_GET['eventid']))
{
	$eventid = $_GET['eventid'];

	// Set all event users to 'unconfirmed' and increment their attendance counter
	if (isset($_GET['reset']) && $_GET['reset'] == 1)
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

		if (!$wpdb->update('s_eventusers', 
				array('status'=>'Unconfirmed'),
				array('eventid'=>$eventid),
				array('%s'), 
				array('%d') ))
		{
			print "ERROR updating! Doh!";
			print $wpdb->last_error;
			print "Last query: ".$wpdb->last_query."<br />";
			die();
		}

		header("Location: /events/?eventid=$eventid");
		die();
	}

	if (isset($_GET['toggleuser']))
	{
		$toggleuser = $_GET['toggleuser'];

		if ($toggleuser == "Confirmed")
			$newtoggleuser = "Not Coming";
		else if ($toggleuser == "Not Coming")
			$newtoggleuser = "Confirmed";
		else if ($toggleuser == "Unconfirmed")
			$newtoggleuser = "Confirmed";

		if (!$wpdb->update('s_eventusers', 
				array('status'=>$newtoggleuser),
				array('userid'=>$userid, 'eventid'=>$eventid),
				array('%s'), 
				array('%d', '%d') ))
		{
			print "ERROR updating! Doh!";
			print $wpdb->last_error;
			print "Last query: ".$wpdb->last_query."<br />";
		}

		header("Location: /events/?eventid=$eventid");
		die();
	}

	// Get the logged-in user's status for the event & event info
	$query = $wpdb->prepare("SELECT e.name AS eventname, e.id AS eventid, eu.status, e.info, eu.confirmedcount, eu.unconfirmedcount, eu.notcomingcount, eu.userid
		FROM s_events e, s_eventusers eu
		WHERE eu.userid = %d
		AND eu.eventid = %d
		AND e.id = eu.eventid", $userid, $eventid);

	$rs = $wpdb->get_results($query);

	// FIXME
	if ($rs[0]->status == "Confirmed")
		$rowbg = "green";
	else if ($rs[0]->status == "Not Coming")
		$rowbg = "red";
	else
		$rowbg = "";

	?>
	<h3><?=$rs[0]->eventname?></h3>

	<div class="alert alert-info">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
			<div><?=$rs[0]->info ?></div>
	</div>

	<div class="userrow my <?=$rowbg?>">
		<?php
		printUserCard($rs[0], $eventid);
		?>
	</div>

	<div style="clear:both"></div>

	<?php

	// Show the status of all other users in the event
	$query = $wpdb->prepare("SELECT u.display_name, eu.status, eu.userid, eu.confirmedcount, eu.unconfirmedcount, eu.notcomingcount
			FROM s_eventusers eu, wp_users u
			WHERE eu.eventid = %d
			AND eu.userid = u.ID
			AND eu.userid != %d", $eventid, $userid);

	$rs = $wpdb->get_results($query);

	$ct = 0;
	foreach ($rs as $result)
	{
		// FIXME
		$rowbg="";

		if ($result->status == "Confirmed")
			$rowbg = "green";
		else if ($result->status == "Not Coming")
			$rowbg = "red";
		else
			$rowbg = "";
		?>

		<div class="userrow <?=$ct %2 == 0 ? "" : "alt" ?> <?= $rowbg?>">
		<?php
		printUserCard($result, $eventid);
		?>
		</div>
		<div style="clear:both"></div>

		<?php
		$ct++;
	}
}
else 
{
	print "Userid: $userid<br />";
	echo userphoto($userid);
	// Prepare a list of events users have access to
	$query = $wpdb->prepare("SELECT e.name AS eventname, e.id AS eventid, e.repeat, u.display_name, eu.status
			FROM s_events e, s_eventusers eu, s_organisations o, wp_users u
			WHERE u.ID = %d
			AND eu.userid = u.ID
			AND e.id = eu.eventid
			 AND o.id = e.organisationid", $userid);

	$rs = $wpdb->get_results($query);

	$ct=0;
	foreach ($rs as $result)
	{
		$ct++;

		?>
		<?=$ct?> :: <a href="/events/?eventid=<?=$result->eventid?>"><?= $result->eventname ?></a> :: Status: <?= $result->status?> 
		<?php
	}
}


function printUserCard($userdata, $eventid)
{
?>
	<?php if ($userdata->userid == get_current_user_id() ) 
	{ ?> <div style="float:left; font-size:15pt; transform: rotate(-90deg);margin-top:40px;"><b>YOU</b></div> <?php } ?>
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

//die();

?>
		<div style="float:left; font-size:15pt; transform: rotate(-90deg);margin-top:40px;"><b>YOU</b></div>
		<div class="userphoto"><?=  userphoto($userid);?></div>
		<div style="float:left;"><b><?= wp_get_current_user()->display_name ?></b>
			<div class="button"><?= $rs[0]->status?> (<a href="?eventid=<?=$eventid?>&toggleuser=<?=$rs[0]->status?>">change status</a>)</div>
			<div>
				<span rel="tooltip" title="Confirmed"><img src="http://trog.qgl.org/up/1305/confirmed.png"> <?= $rs[0]->confirmedcount ?></span>
				<span rel="tooltip" title="Unconfirmed"><img src="http://trog.qgl.org/up/1305/unconfirmed.png"> <?= $rs[0]->unconfirmedcount ?></span>
				<span rel="tooltip" title="Not coming"><img src="http://trog.qgl.org/up/1305/notcoming.png"> <?= $rs[0]->notcomingcount ?></span>
			</div>
		</div>


			<div class="userphoto"><?=userphoto($result->userid)?></div>
			<div style="float:left;"><?=$result->display_name ?> 
				<div class="button"><?= $result->status?></div>
				<div>
					<img src="http://trog.qgl.org/up/1305/confirmed.png"> <?= $result->confirmedcount ?>
					<img src="http://trog.qgl.org/up/1305/unconfirmed.png"> <?= $result->unconfirmedcount ?>
					<img src="http://trog.qgl.org/up/1305/notcoming.png"> <?= $result->notcomingcount ?>
				</div>
			</div>
