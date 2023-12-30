<?php

namespace DT\Plugin\Conditions;

use DT\Plugin\CodeZone\Router\Conditions\Condition;

class IsFrontendPath implements Condition {

	/**
	 * Test if the path is a frontend path.
	 *
	 * @return bool Returns true if the path is a frontend path.
	 */
	public function test(): bool {
		return ! is_admin();
	}
}