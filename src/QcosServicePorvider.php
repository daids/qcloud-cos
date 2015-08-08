<?php 
namespace Daids\QcloudCos;

use Illuminate\Support\ServiceProvider;

class QcosServicePorvider extends ServiceProvider
{
	protected $defer = true;
	
	public function register()
	{
		$this->app->bind('qcloud.cos', function($app){
			return new CosApi($app->config);
		});
	}

	public function provides()
	{
		return ['qcloud.cos'];
	}
}