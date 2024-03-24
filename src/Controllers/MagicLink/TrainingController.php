<?php

namespace DT\Autolink\Controllers\MagicLink;

use DT\Autolink\Illuminate\Http\Request;
use DT\Autolink\Illuminate\Http\Response;
use function DT\Autolink\template;

class TrainingController
{
	public function show( Request $request, Response $response, $key )
	{

		$training_data = $this->get_all_trainings_data();
		$data = json_encode( $training_data );
		$training_data_json_escaped = htmlspecialchars( $data );

		return template( 'training', compact(
			'data',
			'training_data',
			'training_data_json_escaped'
		) );
	}

	protected function get_all_trainings_data()
	{
		// Get the apps array from the option
		$trainings_array = get_option( 'dt_home_trainings', [] );

		// Sort the array based on the 'sort' key
		usort($trainings_array, function ( $a, $b ) {
			return $a['sort'] - $b['sort'];
		});

		return $trainings_array;
	}

	public function data( Request $request, Response $response, $key )
	{
		$user = wp_get_current_user();
		$data = [
			'user_login' => $user->user_login,
		];
		$response->setContent( $data );

		return $response;
	}
}