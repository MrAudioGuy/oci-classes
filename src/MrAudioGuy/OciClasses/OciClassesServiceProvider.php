<?php namespace MrAudioGuy\OciClasses;

use Illuminate\Support\ServiceProvider;

class OciClassesServiceProvider extends ServiceProvider {

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
		$this->package('mr-audio-guy/oci-classes');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$loader = $this->app['config']->getLoader();

		// Get environment name
		$env = $this->app['config']->getEnvironment();

		// Add package namespace with path set, override package if app config exists in the main app directory
		if (file_exists(app_path() . '/config/packages/Mr-Audio-Guy/oci-classes')) {
			$loader->addNamespace('oci-classes', app_path() . '/config/packages/Mr-Audio-Guy/oci-classes');
		} else {
			$loader->addNamespace('oci-classes', __DIR__ . '/../../config');
		}

		$config = $loader->load($env, 'config', 'oci-classes');

		$this->app['config']->set('oci-classes::config', $config);
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
