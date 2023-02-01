#!/bin/bash
# This script is run by the wp-graphql entrypoint.sh script as app-setup.sh.

# Run the base wp-graphql image setup script then our setup.
. /usr/local/bin/original-app-setup.sh

PLUGINS_DIR=${PLUGINS_DIR-.}
ACF_LICENSE_KEY=${ACF_LICENSE_KEY-.}
ACF_VERSION=${ACF_VERSION-"latest"}
ACF_PRO=${ACF_PRO-false}

# Export the plugin slug for use when running the codeception tests
# (The slug is different for Free and Pro)
export ACF_PLUGIN_SLUG=${ACF_PLUGIN_SLUG-'advanced-custom-fields/acf.php'}

# If an ACF_VERSION is passed, use it, else the latest version will be downloaded
ACF_PRO_DOWNLOAD_VERSION=""

if [[ -n ${ACF_VERSION} && "${ACF_VERSION}" != "latest" ]]; then
	ACF_PRO_DOWNLOAD_VERSION="&t=${ACF_VERSION}"
fi

echo "Plugins dir ($PLUGINS_DIR)"
echo "ACF_VERSION ($ACF_VERSION)"
echo "ACF_PRO ($ACF_PRO)"

if [ ! -f "${PLUGINS_DIR}/wp-graphql/wp-graphql.php" ]; then
    # WPGRAPHQL_VERSION in format like v1.2.3 or latest
    echo "Install wp-graphql version (${WPGRAPHQL_VERSION})"
    if [[ -z ${WPGRAPHQL_VERSION} || "${WPGRAPHQL_VERSION}" == "latest" ]]; then
        echo "Installing latest WPGraphQL from WordPress.org"
        wp plugin install wp-graphql --activate --allow-root
    else
    	echo "Installing WPGraphQL from Github"
        wp plugin install "https://downloads.wordpress.org/plugin/wp-graphql.${WPGRAPHQL_VERSION-1.4.3}.zip" --allow-root --activate
    fi
fi

# Activate the plugin
wp plugin activate wp-graphql-acf-redux --allow-root


# If ACF_PRO is not true, or the license key is a default value, we'll be using the FREE version of ACF
if [[ true != ${ACF_PRO} || '.' == ${ACF_LICENSE_KEY} || 'Your License Key' == ${ACF_LICENSE_KEY} ]]; then
	echo "ACF version: ${ACF_VERSION}"

	# The slug is needed when the tests run, so we set it here
	ACF_PLUGIN_SLUG="advanced-custom-fields/acf.php"
	if [[ -z ${ACF_VERSION} || "${ACF_VERSION}" == "latest" ]]; then
		echo "Installing ACF FREE (latest) from wordpress.org"
		wp plugin install advanced-custom-fields --allow-root --activate
	else
		echo "Installing ACF FREE (v${ACF_VERSION}) from wordpress.org"
		wp plugin install advanced-custom-fields --version=$ACF_VERSION --allow-root --activate
	fi
else

	# The slug is needed when the tests run, so we set it here
	ACF_PLUGIN_SLUG="advanced-custom-fields-pro/acf.php"

	if [ ! -d ${PLUGINS_DIR}/advanced-custom-fields-pro ]; then
		echo "Installing ACF Pro from AdvancedCustomFields.com"
		wp plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_LICENSE_KEY}${ACF_PRO_DOWNLOAD_VERSION}" --activate --allow-root --queit
	else
		echo "Warning: Advanced Custom Fields Pro plugin already installed"
	fi
fi

## List the plugins that were activated to ensure ACF Free or Pro was properly activated
wp plugin list --allow-root
