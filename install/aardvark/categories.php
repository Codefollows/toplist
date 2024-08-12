<?php

//Cateogry description and keywords
$DB->query("ALTER TABLE {$CONF['sql_prefix']}_categories
	ADD `cat_description` varchar( 320 ),
	ADD `cat_keywords` varchar( 155 ),
	ADD `old_slugs` TEXT NULL DEFAULT NULL AFTER `category`,
	ADD `category_slug` VARCHAR(255) default '' NOT NULL AFTER `category`
", __FILE__, __LINE__);

$result = $DB->query("SELECT category FROM {$CONF['sql_prefix']}_categories ORDER BY category", __file__, __line__);
while ($row = $DB->fetch_array($result)) {

	// Strip unwanted chars from category
	$category = preg_quote($row['category']);
	$slug     = preg_replace('/([^\p{L}\p{N}]|[\-])+/u', '-', $category);
	$slug     = trim($slug, '-');
	$slug_sql = $DB->escape($slug);
	$cat_sql  = $DB->escape($row['category']);

	$DB->query("UPDATE {$CONF['sql_prefix']}_categories SET `category_slug` = '{$slug_sql}' WHERE category = '{$cat_sql}'", __FILE__, __LINE__);
}