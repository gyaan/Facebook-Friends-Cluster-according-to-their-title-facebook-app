<?php

/**
 * This sample app is provided to kickstart your experience using Facebook's
 * resources for developers.  This sample app provides examples of several
 * key concepts, including authentication, the Graph API, and FQL (Facebook
 * Query Language). Please visit the docs at 'developers.facebook.com/docs'
 * to learn more about the resources available to you
 */

// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');

// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
    header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');


/*****************************************************************************
 *
 * The content below provides examples of how to fetch Facebook data using the
 * Graph API and FQL.  It uses the helper functions defined in 'utils.php' to
 * do so.  You should change this section so that it prepares all of the
 * information that you want to display to the user.
 *
 ****************************************************************************/

require_once('sdk/src/facebook.php');


//echo AppInfo::appID();
//echo AppInfo::appSecret();
//echo '<br>gyani';

$facebook = new Facebook(array(
    'appId'  => AppInfo::appID(),
    'secret' => AppInfo::appSecret(),
//  'sharedSession' => true,
//  'trustForwarded' => true,
));

$user_id = $facebook->getUser();
if ($user_id) {
    try {
        // Fetch the viewer's basic information
        $basic = $facebook->api('/me');
    } catch (FacebookApiException $e) {
        // If the call fails we check if we still have a user. The user will be
        // cleared if the error is because of an invalid accesstoken
        if (!$facebook->getUser()) {
            header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
            exit();
        }
    }

    // This fetches some things that you like . 'limit=*" only returns * values.
    // To see the format of the data you are retrieving, use the "Graph API
    // Explorer" which is at https://developers.facebook.com/tools/explorer/

    $accessToken = $facebook->getAccessToken();
    $friends = $facebook->api('me/friends?fields=id,name,work&access_token='.$accessToken.'');


    $FriendHaveTitle = array();
    $FriendDontHaveTitle=array();

    foreach($friends['data'] as $friend) {

        if(array_key_exists('work',$friend)){
            $temp = false; //if friend have work details but don't have position
            foreach($friend['work'] as $work){
                if(array_key_exists('position',$work)){
                    $FriendHaveTitle[$work['position']['name']][]=array(
                        'id'=>$friend['id'],
                        'name'=>$friend['name']
                    );
                    $temp = true;
                }

            }
            if($temp===false){
                $FriendDontHaveTitle[]=array(
                    'id'=>$friend['id'],
                    'name'=>$friend['name'],
                );
            }
        }
        else{
            $FriendDontHaveTitle[]=array(
                'id'=>$friend['id'],
                'name'=>$friend['name'],
            );
        }


    }

    // using this app
    $app_using_friends = $facebook->api(array(
        'method' => 'fql.query',
        'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
    ));
}

// Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());
$app_name = idx($app_info, 'name', '');

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title> welocme to gyani's facebook app <?php echo he($app_name); ?></title>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" type="text/css" />

    <!--[if IEMobile]>
    <link rel="stylesheet" href="mobile.css" media="screen" type="text/css"  />
    <![endif]-->

    <!-- These are Open Graph tags.  They add meta data to your  -->
    <!-- site that facebook uses when your content is shared     -->
    <!-- over facebook.  You should fill these tags in with      -->
    <!-- your data.  To learn more about Open Graph, visit       -->
    <!-- 'https://developers.facebook.com/docs/opengraph/'       -->
    <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="My first app" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content=" Friends Cluster">
    <meta name="author" content="gyaneshwar pardhi">

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>
    <script type="text/javascript">
        function logResponse(response) {
            if (console && console.log) {
                console.log('The response was', response);
            }
        }
        });
    </script>

    <!--[if IE]>
    <script type="text/javascript">
        var tags = ['header', 'section'];
        while(tags.length)
            document.createElement(tags.pop());
    </script>
    <![endif]-->
</head>
<body>
<div id="fb-root"></div>
<script type="text/javascript">
    window.fbAsyncInit = function() {
        FB.init({
            appId      : '<?php echo AppInfo::appID(); ?>', // App ID
            channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
            status     : true, // check login status
            cookie     : true, // enable cookies to allow the server to access the session
            xfbml      : true // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
            // We want to reload the page now so PHP can read the cookie that the
            // Javascript SDK sat. But we don't want to use
            // window.location.reload() because if this is in a canvas there was a
            // post made to this page and a reload will trigger a message to the
            // user asking if they want to send data again.
            window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
    };

    // Load the SDK Asynchronously
    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>

<?php if (isset($basic)) { ?>
    <h2>Welcomes <strong><?php echo he(idx($basic, 'name')); ?></strong></h2>
    <p>Here is your Friends Clusters According to their Titles:</p>
<?php } else { ?>
    <div>
        <h2>Welcome</h2>
        <div class="fb-login-button" data-scope="user_photos,friends_work_history"></div>
    </div>
<?php } ?>

<?php
if ($user_id) {
    ?>
    <!-- here we gone display list -->
    <?php

    if(count($FriendHaveTitle)> 0){
        foreach($FriendHaveTitle as $title=>$friends) {?>
            <div class="list" style="padding: 10px 10px 10px 10px;">
                <h3><?php echo $title;?></h3>
                <ul class="friends">
                    <?php foreach ($friends as $friend) {
                        // Extract the pieces of info we need from the requests above
                        $id = idx($friend, 'id');
                        $name = idx($friend, 'name');
                        ?>
                        <li style="display: inline-block;width: 250px;">
                            <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
                                <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
                                <?php echo he($name); ?>
                            </a>
                        </li>
                    <?php }?>
                </ul>
            </div>
        <?php }
    }
    else{
        echo "no one specified their title/titles in your friend list";
    }
    ?>
    </div>

    <?php

    if(count($FriendDontHaveTitle)>0) {?>
        <div class="list" style="padding: 10px 10px 10px 10px;">
            <h3><?php echo "Don't have title"?></h3>
            <ul class="friends">
                <?php foreach ($FriendDontHaveTitle as $friend) {
                    // Extract the pieces of info we need from the requests above
                    $id = idx($friend, 'id');
                    $name = idx($friend, 'name');
                    ?>
                   <li style="display: inline-block;width: 250px;">
                        <a href="https://www.facebook.com/<?php echo he($id); ?>" target="_top">
                            <img src="https://graph.facebook.com/<?php echo he($id) ?>/picture?type=square" alt="<?php echo he($name); ?>">
                            <?php echo he($name); ?>
                        </a>
                    </li>
                <?php }?>
            </ul>
        </div>
    <?php
    }else{
        echo 'All friend specified their titles';
    }?>
    </div>

<?php
}
?>
<hr>
<p>&copy; <a href="http://www.gyaneshwar.net">Gyaneshwar.Net</a></p>
</body>
</html>
