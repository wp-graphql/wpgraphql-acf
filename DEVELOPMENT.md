# Configuring your .env file

The `.env` file is used to configure your testing environment, whether you are running the tests locally (with `composer install-test-env`) or in a docker container. Your first step is to copy the `.env.dist` file to `.env` and update the values to match your environment.

## ACF Pro + ACF Extended Pro

To test with ACF Pro and ACF Extended Pro, you need to update the `ACF_PLUGIN_SLUG`, `ACF_LICENSE_KEY`, `ACF_EXTENDED_PLUGIN_SLUG` and `ACF_EXTENDED_LICENCE_KEY` values in your `.env` file.
```diff
# .env

# Change to 'advanced-custom-fields-pro' and add your license key to test against ACF PRO
- ACF_PLUGIN_SLUG="advanced-custom-fields"
+ ACF_PLUGIN_SLUG="advanced-custom-fields-pro"
- # ACF_LICENSE_KEY="Your License Key"
+ ACF_LICENSE_KEY="Your Actual License Key"

# Change to 'acf-extended-pro' and add your license key to test against ACF Extended PRO
- ACF_EXTENDED_PLUGIN_SLUG="acf-extended"
+ ACF_EXTENDED_PLUGIN_SLUG="acf-extended-pro"
- # ACF_EXTENDED_LICENSE_KEY="Your License Key"
+ ACF_EXTENDED_LICENSE_KEY="Your Actual License Key"
```

Additionally, you will need to copy your `phpstan.neon.dist` file to `phpstan.neon` and update the `scanDirectories` value to the correct path for your ACF Pro and ACF Extended Pro plugins.
```diff
# phpstan.neon

excludePaths:
	analyseAndScan:
			- */node_modules/*
			- */vendor/*
	scanDirectories:
-		- ../advanced-custom-fields/
+		- ../advanced-custom-fields-pro/
-		- ../acf-extended/
+		- ../acf-extended-pro/
	scanFiles:
		- vendor/axepress/wp-graphql-stubs/wp-graphql-stubs.php
```diff

# Using Docker For Local Development

## WordPress Site

The `app` docker image starts a running WordPress site with the local wpgraphql-acf directory installed and activated. Local changes to the source code is immediately reflects in the app.

1. Build the plugin.
1.1 `composer install` or `docker run -v $PWD:/app composer --ignore-platform-reqs install`
1. Run `composer build-app` to build the `app` docker image.
1. Run `composer build-test` to build the `testing` docker image.
1. Run `composer run-app` to start the WordPress site.
1. Browse to http://localhost:8091/ to access the running WordPress app.
1. Browse to http://localhost:8091/wp-admin/ to access the admin dashboard. Username is 'admin'. Password is 'password'.

## Testing Suite

The `testing` docker image starts a running WordPress and runs the codeception test suites.

1. Run `composer build-test` to build the `testing` docker image.
1. Run `composer run-test` to start the `testing` image and run the codeception tests.

# Using XDebug

## Local WordPress Site With XDebug
Use an environment variable USING_XDEBUG to start the docker image and WordPress with xdebug configured to use port 9003 to communicated with your IDE.

```
export USING_XDEBUG=1
composer run-app
```

Start the debugger in your IDE. Set breakpoints.

Load the app in http://localhost:8091/.

## Using XDebug With Tests

Use the environment variable USING_XDEBUG to run tests with xdebug configured to use port 9003 to communicated with your IDE.

```
export USING_XDEBUG=1
composer run-test
```

Start the debugger in your IDE. Set breakpoints.

## Configure VSCode IDE Launch File

Create or add the following configuration to your .vscode/launch.json in the root directory. Restart VSCode. Start the debug listener before running the app or testing images.

If you have WordPress core files in a directory for local development, you can add the location to the `pathMappings` for debug step through.

```
{
    "version": "0.2.0",
    "configurations": [
		{
			"name": "Listen for Xdebug",
			"type": "php",
			"request": "launch",
			"port": 9003,
			"xdebugSettings": {
				"max_children": 128,
				"max_data": 1024,
				"max_depth": 3,
				"show_hidden": 1
			},
			"pathMappings": {
				"/var/www/html/wp-content/plugins/wpgraphql-acf": "${workspaceFolder}",
				"/var/www/html/wp-content/plugins/wp-graphql": "${workspaceFolder}../wp-graphql",
				"/var/www/html": "${workspaceFolder}/wordpress",
			}
		}
    ]
}
