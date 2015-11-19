<?php
require_once('../../../wp-load.php');

if (isset($_GET['ingredients'])) {
    $ingredient_array = (array)$_GET["ingredients"];
    if (count($ingredient_array)) {
        foreach ($ingredient_array as $ingredient_string) {
            $term = get_term_by('name', $ingredient_string, 'ingredient');
            if ($term) {
                $ingredient_ids[] = $term->term_id;
            }
        }
    }
}

$recipe_results = $sc_recipes_post_type->list_recipes( 0,-1,'', '', array(), array(), array(), array(), $ingredient_ids);
$count = count($recipe_results['results']);
for($i = 0; $i < $count;$i++ ) {
    $default_attr = array(  'alt'	=>  $recipe_results['results'][$i]->post_title );
    $get_image = get_the_post_thumbnail( $recipe_results['results'][$i]->ID, 'medium', $default_attr);
    $recipe_results['results'][$i]->image = $get_image;
}
if ( count($recipe_results) > 0 && $recipe_results['total'] > 0 ) {
    echo json_encode($recipe_results['results']);
}