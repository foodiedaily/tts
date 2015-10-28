<?php

if ( !defined( 'ABSPATH' ) ) {
    die;
}
if ( class_exists( 'RatingWidgetPlugin' ) && !class_exists( 'RW_AbstractExtension' ) ) {
    abstract class RW_AbstractExtension
    {
        /**
         * @return string
         */
        public abstract function GetSlug();
        
        /**
         * @return boolean
         */
        public abstract function HasSettingsMenu();
        
        /**
         * @return array
         */
        public abstract function GetSettingsMenuItem();
        
        /**
         * @return array
         */
        public abstract function GetSettings();
        
        /**
         * @return array
         */
        public abstract function GetRatingClasses();
        
        /**
         * @return array
         */
        public abstract function GetDefaultOptions();
        
        /**
         * @return array
         */
        public abstract function GetDefaultAlign();
        
        /**
         * @param $class string
         *
         * @return string
         */
        public abstract function GetAlignOptionNameByClass( $class );
        
        /**
         * If true, page/post/comment ratings would be disabled on current page.
         *
         * @return boolean
         */
        public abstract function BlockLoopRatings();
        
        /**
         * Check if the extension supports ratings for current page.
         *
         * @return boolean
         */
        public abstract function IsExtensionPage();
        
        /**
         * Return the rating class of the current's page.
         *
         * @return string
         */
        public abstract function GetCurrentPageClass();
        
        public abstract function Hook( $rclass );
        
        /**
         * Retrieve unique global rating ID for the specific element.
         *
         * @param $element_id int
         * @param $rclass string
         *
         * @return mixed
         */
        public abstract function GetRatingGuid( $element_id, $rclass );
        
        /**
         * @return array
         */
        public abstract function GetTopRatedInfo();
        
        /**
         * @param $type string
         * @param $rating object
         *
         * @return array of string
         */
        public abstract function GetElementInfoByRating( $type, $rating );
    
    }
}