<?php
/* Template Name: BigOven */
/*
 * The template for submit recipe page *
 * @package WordPress
 * @subpackage SocialChef
 * @since SocialChef 1.0
 */

get_header('buddypress');
SocialChef_Theme_Utils::breadcrumbs();
get_sidebar('under-header'); ?>

<div class="bigoven">

</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script>
    function getRecipeJson() {
        var apiKey = "Et3G368R2cTiwcH59XH9GnqK9NtjiqG7";
        var recipeId = 196149;
        var url = "http://api.bigoven.com/recipe/" + recipeId + "?api_key=" + apiKey;
        $.ajax({
            type: "GET",
            dataType: 'json',
            cache: false,
            url: url,
            success: function (data) {
            console.log(data);
            $(".bigoven").html(data.Category);
            $(".bigoven").html(data.Instructions);
        }
        });
    }
    getRecipeJson();
</script>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script>
        function getRecipeJson() {
            var apiKey = "Et3G368R2cTiwcH59XH9GnqK9NtjiqG7";
            var titleKeyword = "steak";
            var url = "http://api.bigoven.com/recipes?pg=1&rpp=25&title_kw="
                + titleKeyword
                + "&api_key="+apiKey;
            $.ajax({
                type: "GET",
                dataType: 'json',
                cache: false,
                url: url,
                success: function (data) {
                    alert('success');
                    //console.log(data);
                }
            });
        }
        getRecipeJson();
    </script>


<?php
get_footer( 'buddypress' ); ?>