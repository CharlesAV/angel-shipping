<?php namespace Angel\Shipping;

use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('angel/shipping');
		
		include __DIR__ . '../../../routes.php';

		$bindings = array(
			'Shipping' => '\Angel\Shipping\Shipping',
			'ShippingUPS' => '\Angel\Shipping\ShippingUPS',
			'ShippingUSPS' => '\Angel\Shipping\ShippingUSPS',
			'ShippingFedEx' => '\Angel\Shipping\ShippingFedEx'
		);
		foreach ($bindings as $name=>$class) {
			$this->app->singleton($name, function() use ($class) {
				return new $class;
			});
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
