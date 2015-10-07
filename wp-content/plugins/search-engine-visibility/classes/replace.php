<?php
defined( 'WPINC' ) or die;

class GD_SEV_Plugin_Replacements {
	private $replacements = array();

	public function __construct( $replacements = array() ) {
		$this->set_replacements( $replacements );
	}

	public function set_replacements( $replacements ) {
		foreach ( (array) $replacements as $replacement ) {
			$this->replacements[] = new GD_SEV_Plugin_Replacement( $replacement );
		}
	}

	public function get_replacements() {
		return $this->replacements;
	}
}

class GD_SEV_Plugin_Replacement {
	private $old;
	private $new;
	private $id;
	private $mode; // 'first' or 'all'
	private $conditions = array();

	public function __construct( $replacement ) {
		foreach ( array( 'old', 'new', 'mode', 'id', 'conditions' ) as $prop ) {
			if ( isset( $replacement->$prop ) ) {
				$this->$prop = $replacement->$prop;
			}
		}
	}

	public function contains( $haystack, $needle ) {
		return strpos( $haystack, $needle ) !== false;
	}

	public function has_conditions() {
		return count( $this->conditions ) > 0;
	}

	public function conditions_match( $content ) {
		foreach ( (array) $this->conditions as $cond ) {
			if ( 'contains' !== $cond->type ) {
				return false; // Don't know how to match this condition
			}
			if ( $cond->invert === $this->contains( $content, $cond->text ) ) {
				return false;
			}
		}
		return true; // Haven't bailed yet, it must be good!
	}

	public function str_replace_first( $search, $replace, $subject ) {
		$pos = strpos( $subject, $search );
		if ( $pos !== false ) {
			$subject = substr_replace( $subject, $replace, $pos, strlen( $search ) );
		}
		return $subject;
	}

	public function replace( $content ) {
		if ( $this->conditions_match( $content ) ) {
			if ( 'first' === $this->mode ) {
				$content = $this->str_replace_first( $this->old, $this->new, $content );
			} else {
				$content = str_replace( $this->old, $this->new, $content );
			}
		}
		return $content;
	}

	public function unprocess( $content ) {
		$content = preg_replace( '#<p>(.*)</p>#', '$1', $content );
		return $content;
	}

	public function post_content_replace( $content ) {
		$old = $this->unprocess( $this->old );
		$new = $this->unprocess( $this->new );
		if ( 'first' === $this->mode ) {
			$content = $this->str_replace_first( $old, $new, $content );
		} else {
			$content = str_replace( $old, $new, $content );
		}
		return $content;
	}
}
