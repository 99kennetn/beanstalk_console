<?php
$servers = $console->getServers();
if ($server) {
    $serverKey = array_search($server, $servers);
    $serverLabel = is_numeric($serverKey) || empty($serverKey) ? $server : $serverKey;
}
?><!DOCTYPE html>
<html>

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>
            <?php if ($tube) echo $tube . ' - ' ?>
            <?php echo !empty($serverLabel) ? $serverLabel : 'All servers' ?> -
            Beanstalk console
        </title>

        <!-- Bootstrap core CSS -->
        <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="css/customer.css" rel="stylesheet">
        <link href="highlight/styles/magula.css" rel="stylesheet">
        <link rel="shortcut icon" href="assets/favicon.ico">
        <script>
            var url = "./?server=<?php echo $server ?>";
            var contentType = "<?php echo isset($contentType) ? $contentType : '' ?>";
        </script>

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <?php if (!empty($servers)): ?>
        <body>
        <?php else: ?>
        <body class="no-nav">
        <?php endif ?>

        <?php if (!empty($servers)): ?>
            <div class="navbar navbar-fixed-top navbar-default" role="navigation">
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="./?">Beanstalk console</a>
                    </div>
                    <div class="collapse navbar-collapse">
                        <ul class="nav navbar-nav">

                            <?php if ($server): ?>
                                <!-- Server dropdown: current, then All, then remaining -->
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                        <?php echo $serverLabel ?> <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a href="./?">All servers</a></li>
                                        <?php foreach (array_diff($servers, array($server)) as $key => $serverItem): ?>
                                            <li><a href="./?server=<?php echo htmlspecialchars($serverItem) ?>"><?php echo empty($key) || is_numeric($key) ? htmlspecialchars($serverItem) : $key ?></a></li>
                                        <?php endforeach ?>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <!-- Server dropdown: All, then remaining -->
                                <li class="dropdown">
                                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                        All servers <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <?php foreach ($servers as $key => $serverItem): ?>
                                            <li><a href="./?server=<?php echo htmlspecialchars($serverItem) ?>"><?php echo empty($key) || is_numeric($key) ? htmlspecialchars($serverItem) : $key ?></a></li>
                                        <?php endforeach ?>
                                    </ul>
                                </li>
                            <?php endif ?>
                        </ul>

                        <ul class="nav navbar-nav navbar-right">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding:4px !important;"><img src="assets/hamburger.png" width="32px" height="32px"></a>
                                <ul class="dropdown-menu" role="menu">
                                <?php if (@$config['auth']['enabled']) { ?>
                                <li class="dropdown">
                                    <a target="_blank" href="./?logout=true">logout <span class="glyphicon glyphicon-log-out" aria-hidden="true"></span></a>
                                </li>
                                <li class="divider"></li>
                            <?php } ?>
                                    <li><a style="font-size:9px" href="https://github.com/ptrofimov/beanstalk_console">Beanstalk console (original github)</a></li>
                                    <li><p style="font-size:8px; padding:3px 20px">This fork is maintained by <a href="https://github.com/99kennetn">99kennetn</a></p></li>
                                </ul>
                            </li>

                        </ul>
                        <?php if (isset($server, $tube) && $server && $tube) { ?>
                            <form  class="navbar-form navbar-right" style="margin-top:5px;margin-bottom:0px;" role="search" action="" method="get">
                                <input type="hidden" name="server" value="<?php echo $server; ?>"/>
                                <input type="hidden" name="tube" value="<?php echo urlencode($tube); ?>"/>
                                <input type="hidden" name="state" value="<?php echo $state; ?>"/>
                                <input type="hidden" name="action" value="search"/>
                                <input type="hidden" name="limit" value="<?php echo empty($_COOKIE['searchResultLimit']) ? 25 : $_COOKIE['searchResultLimit']; ?>"/>
                            </form>
                        <?php } ?>
                    </div>
                    <!--/.nav-collapse -->
                </div>
            </div>

            <div class="container">
            <?php endif ?>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $item): ?>
                    <p class="alert alert-error"><span class="label label-important">Error</span> <?php echo $item ?></p>
                <?php endforeach; ?>
            <?php else: ?>
                <?php if (isset($_tplPage)): ?>
                    <?php include(dirname(__FILE__) . '/' . $_tplPage . '.php') ?>
                <?php elseif (!$server): ?>
                    <div id="idServers">
                        <?php
                        include(dirname(__FILE__) . '/serversList.php');
                        ?>
                    </div>
                    <div id="idServersCopy" style="display:none"></div>
                <?php elseif (!$tube):
                    ?>
                    <div id="idAllTubes">
                        <?php require_once dirname(__FILE__) . '/allTubes.php'; ?>
                    </div>
                    <div id='idAllTubesCopy' style="display:none"></div>
                <?php elseif (!in_array($tube, $tubes)):
                    ?>
                    <?php echo sprintf('Tube "%s" not found or it is empty', $tube) ?>
                    <br><br><a href="./?server=<?php echo $server ?>"> << back </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script src='assets/vendor/jquery/jquery.js'></script>
        <script src="js/jquery.color.js"></script>
        <script src="js/jquery.cookie.js"></script>
        <script src="js/jquery.regexp.js"></script>
        <script src="assets/vendor/bootstrap/js/bootstrap.min.js"></script>
        <?php if (isset($_COOKIE['isDisabledJobDataHighlight']) and $_COOKIE['isDisabledJobDataHighlight'] != 1) { ?>
            <script src="highlight/highlight.pack.js"></script>
            <script>hljs.initHighlightingOnLoad();</script><?php } ?>
        <script src="js/customer.js"></script>
    </body>
</html>
