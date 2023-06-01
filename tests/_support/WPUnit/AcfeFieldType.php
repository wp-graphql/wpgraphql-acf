<?php

namespace Tests\WPGraphQLAcf\WPUnit;

class AcfeFieldType extends \acf_field  {
	public function __construct( $name ) {
		$this->name = $name;
		$this->label = $name;
		parent::__construct();
	}
}
