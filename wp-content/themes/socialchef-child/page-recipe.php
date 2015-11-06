<?php
/* Template Name: Recipe page */
/*
 * The template for displaying a custom search page
 * @package WordPress
 * @subpackage SocialChef
 * @since SocialChef 1.0
 */
$recipe_id =$_GET['recipe_id'];
get_header('buddypress');
SocialChef_Theme_Utils::breadcrumbs();
get_sidebar('under-header');
if ($recipe_id) {
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script>
        var recipe_id = <?php echo $recipe_id; ?>;
        var apiKey = "Et3G368R2cTiwcH59XH9GnqK9NtjiqG7";
        var url = "http://api.bigoven.com/recipe/" + recipe_id + "?api_key=" + apiKey;
        var recipe = null;
        $.when($.ajax({
                type: "GET",
                dataType: 'json',
                cache: false,
                url: url,
                success: function (data) {
//                    console.log(data);
                    recipe = data;
                }
            })).then(function() {
            console.log(recipe);
            if(recipe.ImageURL) {
                var image = recipe.ImageURL;
            } else {
                var image = recipe.HeroPhotoUrl;
            }
            $("header.s-title h1").prepend(recipe.Title);
            if(recipe.ActiveMinutes != 0 && recipe.ActiveMinutes ) {
                $(".prep_time").prepend(recipe.ActiveMinutes);
            } else {
                $(".prep_time").html("N/A");
            }
            if(recipe.TotalMinutes != 0 && recipe.TotalMinutes ) {
                $(".cook_time").prepend(recipe.TotalMinutes);
            } else {
                $(".cook_time").html("N/A");
            }
            if(recipe.YieldNumber != 0 && recipe.YieldNumber ) {
                $(".people_served").prepend(recipe.YieldNumber);
            } else {
                $(".people_served").html("N/A");
            }
            $(".recipe_image").attr("src", image);
            $(".recipe_description").prepend(recipe.Description);
            $(".recipe_instructions").append("<li>" + recipe.Instructions + "</li>");
            for(var i = 0; i < recipe.Ingredients.length; i++) {
                var ingredient = recipe.Ingredients[i];
                if(ingredient.MetricQuantity && ingredient.MetricQuantity != 0) {
                    $(".ingredients").append('<dt>' + ingredient.MetricQuantity.toFixed(2) + ' ' + ingredient.MetricUnit + '</dt>');
                } else {
                    $(".ingredients").append('<dt>N/A</dt>');
                }
                    $(".ingredients").append('<dd itemprop="ingredients"><a href="http://tts.dev/ingredient/' + ingredient.Name + '">' + ingredient.Name + '</a></dd>');
            }
        });


    </script>
<div class="big_oven_recipe">

    <div class="row">
        <div itemscope itemtype="http://schema.org/Recipe">
            <header class="s-title">
                <h1 itemprop="name" class="entry-title" style="padding: 0 15px 10px"></h1>
            </header>
            <div class="woocommerce">
                <p class="stars">
                    <span>
                        <a class="star-5 active" href="#"style="margin-left: 3%" >5</a>
                    </span>
                </p>
            </div>
            <!--content-->
            <section class="content three-fourth">
                <!--recipe-->
                <article id="recipe-<?php echo $recipe_id; ?>" class="recipe">
                    <div class="row">
                        <!--one-third-->
                        <div class="one-third entry-header">
                            <dl class="basic">
                                    <dt><?php _e('Preparation time', 'socialchef'); ?></dt>
                                    <dd itemprop="prepTime" class="prep_time"
                                        content=""><?php _e(' mins', 'socialchef'); ?></dd>
                                    <dt><?php _e('Cooking time', 'socialchef'); ?></dt>
                                    <dd itemprop="cookTime" class="cook_time"><?php _e(' mins', 'socialchef'); ?></dd>
                                    <dt><?php _e('Difficulty', 'socialchef'); ?></dt>
                                    <dd>
                                        <a href="http://tts.dev/difficulty/moderate/">moderate</a>
                                    </dd>
                                    <dt><?php _e('Serves', 'socialchef'); ?></dt>
                                    <dd class="people_served"><?php _e(' people', 'socialchef'); ?></dd>

                            </dl>

                            <dl class="user">
                                    <dt><?php _e('Meal course', 'socialchef'); ?></dt>
                                    <dd>
                                        <a href="http://tts.dev/meal-course/dinner/">Dinner</a>
                                    </dd>
                            </dl>



                                <dl class="ingredients">

                                </dl>


                            <div class="print">
                                <a class="" onclick="window.print();" href="#"><i class="ico eldorado_print"></i>
                                    <span><?php _e('Print recipe', 'socialchef'); ?></span></a>
                            </div>
                        </div>
                        <!--// one-third -->
                        <!--two-third-->
                        <div class="two-third">
                                <div class="image"><img itemprop="image" src="" class="recipe_image" alt=""/></div>
                                <div class="intro" itemprop="description" ><div class="recipe_description"></div></div>

                            <div class="instructions" itemprop="recipeInstructions">
                                <h2>Instructions</h2>
                                <ol class="recipe_instructions">
                                </ol>
                            </div>
                        </div>
                        <!--//two-third-->
                    </div>
                    <!--//row-->
                </article>

            </section>
            <?php get_sidebar('right-recipe'); ?>
        </div>
        <!--//hentry-->
    </div><!--//row-->

</div>

    <?php
} else {

}
get_footer( 'buddypress' );
?>