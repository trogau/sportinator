<?php
require_once("event-funcs.php");
?>
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

.adminpanel
{
	float:right;
	border:solid 1px #a7a7a7;
	width:300px;
	height:200px;
	margin-bottom:-20px;
}

</style>

<?php while (have_posts()) : the_post(); ?>
  <?php the_content(); ?>
  <?php wp_link_pages(array('before' => '<nav class="pagination">', 'after' => '</nav>')); ?>
<?php endwhile; ?>

<?php

// Basic initialisation stuff 
// FIXME: should be in some header
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

	// Check to see if the user is admin. This should probably fire at a higher level.
	$isAdmin = isUserAdmin($userid, $eventid);

	// Set all event users to 'unconfirmed' and increment their attendance counter
	// FIXME: needs admin handling
	if (isset($_GET['reset']) && $_GET['reset'] == 1 && $isAdmin == true) 
	{
		resetEvent($eventid, true);
	}

	if (isset($_GET['updateevent']) && isset($_GET['value']))
	{
		$key = $_GET['updateevent'];
		$value = $_GET['value'];

		// Are we an admin?
		if ($isAdmin == true)
		{
			// Check to make sure the updateevent is valid
			switch($key) 
			{
				case "softlimit":
					adminUpdateEventInt($eventid, $key, $value);
					break;
				default:
					die("Invalid request");
			}
		}
		else
			die("Invalid request");
	}


	if (isset($_GET['emailonreset']) && is_numeric($_GET['emailonreset']) )
	{
		$eor = $_GET['emailonreset'];

		if ($eor == 0 || $eor == 1)
		{
			if ($wpdb->update('s_events', 
				array('emailonreset'=>$eor),
				array('id'=>$eventid),
				array('%d'), 
				array('%d') ) === false)
			{
				print "ERROR updating! Doh! ";
				print $wpdb->last_error;
				print "Last query: ".$wpdb->last_query."<br />";
				die();
			}
			else
			{
				wp_redirect("http://www.sportinator.com/events/?eventid=$eventid");
				die();
			}
		}	
	}

	if (isset($_GET['resetday']) && is_numeric($_GET['resetday']) )
	{
		$eor = $_GET['resetday'];

		if ($eor >=0 && $eor <= 6)
		{
			if ($wpdb->update('s_events', 
				array('resetday'=>$eor),
				array('id'=>$eventid),
				array('%d'), 
				array('%d') ) === false)
			{
				print "ERROR updating! Doh! ";
				print $wpdb->last_error;
				print "Last query: ".$wpdb->last_query."<br />";
				die();
			}
			else
			{
				wp_redirect("http://www.sportinator.com/events/?eventid=$eventid");
				die();
			}
		}	
	}

	if (isset($_GET['resettime']) && is_numeric($_GET['resettime']) )
	{
		$eor = $_GET['resettime'];

		if ($eor >=0 && $eor <= 24)
		{
			if ($wpdb->update('s_events', 
				array('resettime'=>$eor),
				array('id'=>$eventid),
				array('%d'), 
				array('%d') ) === false)
			{
				print "ERROR updating! Doh! ";
				print $wpdb->last_error;
				print "Last query: ".$wpdb->last_query."<br />";
				die();
			}
			else
			{
				wp_redirect("http://www.sportinator.com/events/?eventid=$eventid");
				die();
			}
		}	
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
			die();
		}
		$redir = "http://www.sportinator.com/events/?eventid=$eventid";

		wp_redirect($redir);
		die($redir);
	}

	// Get the logged-in user's status for the event & event info
	$query = $wpdb->prepare("SELECT e.emailonreset, e.name AS eventname, e.id AS eventid, eu.status, 
				e.info, eu.confirmedcount, eu.unconfirmedcount, eu.notcomingcount, eu.userid,
				e.resetday, e.resettime, ea.userid AS adminid, e.hardlimit, e.softlimit
		FROM s_events e, s_eventusers eu
		LEFT JOIN s_eventadmins ea ON ea.userid = eu.userid
		WHERE eu.userid = %d
		AND eu.eventid = %d
		AND e.id = eu.eventid", $userid, $eventid);

	$rs = $wpdb->get_results($query);

	if ($rs)
	{
		// FIXME
		if ($rs[0]->status == "Confirmed")
			$rowbg = "green";
		else if ($rs[0]->status == "Not Coming")
			$rowbg = "red";
		else
			$rowbg = "";

		//style="background-image:url('http://trog.qgl.org/up/1306/thu-soccer-header.jpg');background-size:800px 290px;height:300px;"
		?>
	<div>
		<h3><?=$rs[0]->eventname?></h3>

		<div class="alert alert-info">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
				<div><?=$rs[0]->info ?></div>
		</div>

		<?php
		if (!userphoto_exists(get_current_user_id()))
		{
		?>
		<div class="alert alert-error">
			Hey! You don't have a photo set :( Please jump into your 
			<a href="/wp-admin/profile.php">profile</a> and upload a photo. 
		</div>
		<?php }	?>
	</div>

	<?php
	if ($rs[0]->adminid != null)
	{
	?>
	<div class="adminpanel">
	<b>Admin:</b><br />
	Mail users on reset: 

	<div class="btn-group">
		<button class="btn"><?= $rs[0]->emailonreset ? "Yes" : "No" ?></button>
		<button class="btn dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<li><a href='?eventid=<?= $eventid?>&emailonreset=0'>No</a></li>
			<li><a href='?eventid=<?= $eventid?>&emailonreset=1'>Yes</a></li>
		</ul>
	</div>

	<br />Change reset day:

	<div class="btn-group">
		<button class="btn"><?= $s_days[$rs[0]->resetday] ?></button>
		<button class="btn dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<?php
			for ($i=0;$i<7;$i++)
			{
				?><li><a href='?eventid=<?= $eventid?>&resetday=<?=$i?>'><?= $s_days[$i] ?></a></li><?php
			} ?>
		</ul>
	</div>

	<br />Change reset time:

	<div class="btn-group">
		<button class="btn"><?= $rs[0]->resettime ?>:00</button>
		<button class="btn dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<?php
			for ($i=0;$i<24;$i++)
			{
				?><li><a href='?eventid=<?= $eventid?>&resettime=<?=$i?>'><?= $i.":00" ?></a></li><?php
			} ?>
		</ul>
	</div>

	<br />Change hard player limit:

	<div class="btn-group">
		<button class="btn"><?= $rs[0]->hardlimit ?></button>
		<button class="btn dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<?php
			for ($i=0;$i<24;$i++)
			{
				?><li><a href='?eventid=<?= $eventid?>&updateevent=hardlimit&value=<?=$i?>'><?=$i?></a><?php
			} ?>
		</ul>
	</div>

	<br />Change soft player limit:

	<div class="btn-group">
		<button class="btn"><?= $rs[0]->softlimit ?></button>
		<button class="btn dropdown-toggle" data-toggle="dropdown">
		<span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
			<?php
			for ($i=0;$i<24;$i++)
			{
				?><li><a href='?eventid=<?= $eventid?>&updateevent=softlimit&value=<?=$i?>'><?=$i?></a><?php
			} ?>
		</ul>
	</div>

	</div>

	<?php }	?>

		<?php
		// Get the stats on the event as it stands now:
		$query = $wpdb->prepare("SELECT status, COUNT(*) AS ct 
				FROM s_eventusers 
				WHERE eventid = %d 
				GROUP BY status", $eventid);

		$srs = $wpdb->get_results($query);

		?>
		<div class="btn-group">
		<?php
		foreach($srs as $status)
		{
			// FIXME
			if ($status->status == "Confirmed") $color = "green";
			else if ($status->status == "Not Coming") $color = "red";
			else $color = "";
			?>
			<div class="btn <?=$color?>"><?=$status->status?>: <?=$status->ct ?></div>
			<?php	
		}

		?>
		</div>

	<br /><br />

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
				AND eu.userid != %d ORDER BY u.display_name ASC", $eventid, $userid);

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
}
else  // Main events listing
{
	$userinfo =  wp_get_current_user();
	?>
	<div class="userphoto"><?= userphoto($userid) ?></div>
	<div>Events for <?=  $userinfo->display_name ?></div>
	<?php
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
		<div><a href="/events/?eventid=<?=$result->eventid?>"><b><?= $result->eventname ?></b></a> (<?= $result->status?>) </div>
		<?php
	}
}


function adminUpdateEventInt($eventid, $key, $value)
{
	global $wpdb;

	if ($eor == 0 || $eor == 1)
	{
		if ($wpdb->update('s_events', 
			array($key=>$value), 
			array('id'=>$eventid), 
			array('%s', '%d'), 
			array('%d') ) === false)
		{
			print "ERROR updating: ".$wpdb->last_error.". Last query: ".$wpdb->last_query."<br />";
			die();
		}
		else
		{
			wp_redirect("http://www.sportinator.com/events/?eventid=$eventid");
			die();
		}
	}	
}

