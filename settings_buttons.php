<?php
if (!defined('VISIOLIST')) {
  die("This file cannot be accessed directly.");
}

$TMPL = $DB->fetch("SELECT *
                   FROM {$CONF['sql_prefix']}_sites sites, {$CONF['sql_prefix']}_stats stats
                   WHERE sites.username = stats.username AND sites.username = '{$username}'", __FILE__, __LINE__);
if ($rank) {
  $TMPL['rank'] = $rank;
}
else {
  $TMPL['rank'] = $TMPL['old_rank'];
}

$TMPL['average_rating'] = $TMPL['num_ratings'] > 0 ? round($TMPL['total_rating'] / $TMPL['num_ratings'], 0) : 0;

$ranking_periods = array('daily', 'weekly', 'monthly');
$ranking_methods = array('unq_pv', 'tot_pv', 'unq_in', 'tot_in', 'unq_out', 'tot_out');
foreach ($ranking_periods as $ranking_period) {
  foreach ($ranking_methods as $ranking_method) {
    $TMPL["{$ranking_method}_avg_{$ranking_period}"] = 0;
    for ($i = 0; $i < 10; $i++) {
      $TMPL["{$ranking_method}_avg_{$ranking_period}"] = $TMPL["{$ranking_method}_avg_{$ranking_period}"] + $TMPL["{$ranking_method}_{$i}_{$ranking_period}"];
    }
    $TMPL["{$ranking_method}_avg_{$ranking_period}"] = $TMPL["{$ranking_method}_avg_{$ranking_period}"] / 10;
  }
}

$TMPL['average_rating'] = $TMPL['num_ratings'] > 0 ? round($TMPL['total_rating'] / $TMPL['num_ratings'], 0) : 0;

$img = imagecreatefrompng("{$CONF['path']}/images/ranking.png");
$color1 = imagecolorallocate($img, 255, 255, 255); //Set your colors (RGB values)
$color2 = imagecolorallocate($img, 255, 255, 255); //Set your colors
header ("Content-type: image/png");

imagestring($img, 2, 80, 75, $TMPL['tot_pv_0_daily'], $color1); // Stats size and position
imagestring($img, 2, 80, 90, $TMPL['unq_in_0_daily'], $color1); // Stats size and position
//imagestring($img, 2, 80, 84, "{$TMPL['average_rating']}/5", $color1);
imagestring($img, 5, 75 - ((strlen($TMPL['rank']) - 1) * 4), 50, $TMPL['rank'], $color2); // Rank Position
imagepng($img);
?>