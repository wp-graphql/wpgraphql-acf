$j = jQuery.noConflict();

$j(document).ready(function () {

	var graphqlPostTypeSettings = new acf.Model({
		id: 'graphqlPostTypeSettings',
		events: {
			'blur .acf_singular_label': 'onChangeSingularLabel',
			'blur .acf_plural_label': 'onChangePluralLabel'
		},
		onChangeSingularLabel: function(e, $el) {
			const label = $el.val();
			const sanitized = acf.strCamelCase( acf.strSanitize(label) );
			this.updateValue( '#acf_post_type-graphql_single_name', sanitized );
		},
		onChangePluralLabel: function(e, $el) {
			let label = $el.val();
			let sanitized = acf.strCamelCase( acf.strSanitize(label) );
			this.updateValue( '#acf_post_type-graphql_plural_name', sanitized );
		},
		updateValue: function( fieldId, value ) {
			let currentValue = $j(fieldId).val();

			// if there's already a value, do nothing
			if ('' !== currentValue ) {
				return;
			}

			// set the value
			$j( fieldId ).val( value );

		}
	});

});
