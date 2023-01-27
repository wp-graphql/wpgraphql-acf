#!/bin/bash
# This script is run by the wp-graphql entrypoint.sh script as app-setup.sh.

# Run the base wp-graphql image setup script then our setup.
. /usr/local/bin/original-app-setup.sh

PLUGINS_DIR=${PLUGINS_DIR-.}
ACF_LICENSE_KEY=${ACF_LICENSE_KEY-.}
ACF_VERSION=${ACF_VERSION-.}

echo "Plugins dir ($PLUGINS_DIR)"

if [ ! -f "${PLUGINS_DIR}/wp-graphql/wp-graphql.php" ]; then
    # WPGRAPHQL_VERSION in format like v1.2.3 or latest
    echo "Install wp-graphql version (${WPGRAPHQL_VERSION})"
    if [[ -z ${WPGRAPHQL_VERSION} || "${WPGRAPHQL_VERSION}" == "latest" ]]; then
        echo "Installing latest WPGraphQL from WordPress.org"
        wp plugin install wp-graphql --activate --allow-root
    else
    	echo "Installing WPGraphQL from Github"
        wp plugin install "https://downloads.wordpress.org/plugin/wp-graphql.${WPGRAPHQL_VERSION-1.4.3}.zip" --allow-root
    fi
fi

# Activate the plugin
# wp plugin install advanced-custom-fields --activate --allow-root
wp plugin activate wp-graphql-acf-redux --allow-root

# Some version of acf-pro that let's tests pass.
if [ ! -d ${PLUGINS_DIR}/advanced-custom-fields-pro ]; then
	echo "Installing ACF Pro from AdvancedCustomFields.com"
	wp plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_LICENSE_KEY}" --activate --allow-root
else
    echo "Warning: Advanced Custom Fields Pro plugin already installed"
fi
