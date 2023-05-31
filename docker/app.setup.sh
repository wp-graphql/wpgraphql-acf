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
export ACF_EXTENDED_PLUGIN_SLUG=${ACF_PLUGIN_SLUG-'acf-extended/acf-extended.php'}

# If an ACF_VERSION is passed, use it, else the latest version will be downloaded
ACF_PRO_DOWNLOAD_VERSION=""

if [[ -n ${ACF_VERSION} && "${ACF_VERSION}" != "latest" ]]; then
	ACF_PRO_DOWNLOAD_VERSION="&t=${ACF_VERSION}"
fi

echo "Plugins dir ($PLUGINS_DIR)"
echo "ACF_VERSION ($ACF_VERSION)"
echo "ACF_PRO ($ACF_PRO)"

echo "INSTALL jq"
apt-get -y update
apt-get -y install jq

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
wp plugin activate wpgraphql-acf --allow-root


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
		# Using --quiet prevents the license key from being echoed in the test run
		echo "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_LICENSE_KEY}${ACF_PRO_DOWNLOAD_VERSION}"
#		wp plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_LICENSE_KEY}${ACF_PRO_DOWNLOAD_VERSION}" --activate --allow-root --quiet
		wp plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_LICENSE_KEY}${ACF_PRO_DOWNLOAD_VERSION}" --activate --allow-root
	else
		echo "Warning: Advanced Custom Fields Pro plugin already installed"
	fi
fi

# If ACF_EXTENDED_PRO is not true, or the license key is a default value, we'll be using the FREE version of ACF EXTENDED
if [[ true != ${ACF_PRO} || '.' == ${ACF_EXTENDED_LICENSE_KEY} || 'Your License Key' == ${ACF_EXTENDED_LICENSE_KEY} ]]; then

	echo "ACF EXTENDED Version: " ${ACF_EXTENDED_VERSION}
	ACF_EXTENDED_PLUGIN_SLUG="acf-extended/acf-extended.php"
	if [[ -z ${ACF_EXTENDED_VERSION} || "${ACF_EXTENDED_VERSION}" == "latest" ]]; then
		echo "Installing ACF EXTENDED FREE (latest) from wordpress.org"
		wp plugin install acf-extended --allow-root --activate
	else
		echo "Installing ACF EXTENDED FREE (v${ACF_VERSION}) from wordpress.org"
		wp plugin install acf-extended --version=$ACF_EXTENDED_VERSION --allow-root --activate

	fi

else

	# The slug is needed when the tests run, so we set it here
    	ACF_EXTENDED_PLUGIN_SLUG="acf-extended-pro/acf-extended.php"

    	if [ ! -d ${PLUGINS_DIR}/acf-extended-pro ]; then
    		echo "Installing ACF Extended Pro from acf-extended.com"

    		# Make a request to the Easy Digital Downloads endpoint for ACF Extended to get the download link
			# see: https://gist.github.com/acf-extended/b65882979cdf7c4f5e6a0e5ed733aca7#file-acfe-pro-api-download-postman_collection-json
			download_link=$(curl --location --request GET "https://acf-extended.com?edd_action=get_version&license=${ACF_EXTENDED_LICENSE_KEY}&item_name=ACF%20Extended%20Pro&url=https://acf.wpgraphql.com" | jq '.download_link' )


#			# Get the download link from the curl response. Note jq must be installed. Github has it installed but your local machine might not?
#	        download_link=$( jq -n -f "acfe.json" | jq -f .download_link);

#			download_link=$( "$result" | grep -o '"download_link":"[^"]*' | grep -o '[^"]*' | tail -1)

	        # Remove quotes from the download_link
			download_link="${download_link%\"}"
			download_link="${download_link#\"}"

    		# Install the plugin from the download link. --quiet prevents the license key from being leaked
			wp plugin install ${download_link} --allow-root --activate --quiet

    	else
    		echo "Warning: ACF Extended Pro plugin already installed"
    	fi

fi

## List the plugins that were activated to ensure ACF Free or Pro was properly activated
wp plugin list --allow-root
