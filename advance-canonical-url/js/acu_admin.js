( function () {
	'use strict';

	var checkoutLoader;

	function refreshSettingsState() {
		var method = document.querySelector( 'input[name="acu_options[canonical_method]"]:checked' );
		var queryToggle = document.getElementById( 'acu-query-strings' );
		var policy = document.querySelector( '.acu-policy-panel' );

		document.querySelectorAll( '.acu-choice' ).forEach( function ( choice ) {
			var input = choice.querySelector( 'input[type="radio"]' );
			choice.classList.toggle( 'is-selected', Boolean( input && input.checked ) );
		} );

		if ( ! policy ) {
			return;
		}

		var methodOutput = document.getElementById( 'acu-policy-method' );
		var queryOutput = document.getElementById( 'acu-policy-query' );

		if ( method && methodOutput ) {
			methodOutput.textContent = 'advance' === method.value ? policy.dataset.methodAdvance : policy.dataset.methodBasic;
		}

		if ( queryToggle && queryOutput ) {
			queryOutput.textContent = queryToggle.checked ? policy.dataset.queryYes : policy.dataset.queryNo;
		}
	}

	function standardSubmit( form, button, fallbackLabel ) {
		if ( button ) {
			button.querySelector( '.acu-button-label' ).textContent = fallbackLabel;
		}

		window.HTMLFormElement.prototype.submit.call( form );
	}

	function saveWithoutReload( event ) {
		var form = event.currentTarget;
		var button = form.querySelector( '.acu-save-button' );
		var buttonLabel = button ? button.querySelector( '.acu-button-label' ) : null;

		if ( ! window.fetch || ! window.FormData || ! window.acuAdmin ) {
			return;
		}

		event.preventDefault();

		var originalLabel = buttonLabel ? buttonLabel.textContent : '';
		var formData = new window.FormData( form );
		formData.set( 'action', 'acu_save_settings' );
		formData.set( 'nonce', window.acuAdmin.nonce );

		if ( button ) {
			button.disabled = true;
			button.classList.add( 'is-busy' );
			buttonLabel.textContent = window.acuAdmin.savingLabel;
		}

		window.fetch( window.acuAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'save_failed' );
				}

				return response.json();
			} )
			.then( function ( result ) {
				if ( ! result || ! result.success ) {
					throw new Error( 'save_failed' );
				}

				refreshSettingsState();

				if ( button ) {
					button.classList.remove( 'is-busy' );
					button.classList.add( 'is-saved' );
					buttonLabel.textContent = result.data && result.data.message ? result.data.message : window.acuAdmin.savedLabel;

					window.setTimeout( function () {
						button.disabled = false;
						button.classList.remove( 'is-saved' );
						buttonLabel.textContent = originalLabel;
					}, 1400 );
				}
			} )
			.catch( function () {
				standardSubmit( form, button, window.acuAdmin.fallbackLabel );
			} );
	}

	function loadCheckout( config ) {
		if ( window.FS && window.FS.Checkout ) {
			return window.Promise.resolve();
		}

		if ( checkoutLoader ) {
			return checkoutLoader;
		}

		checkoutLoader = new window.Promise( function ( resolve, reject ) {
			var script = document.createElement( 'script' );
			script.src = config.checkoutUrl;
			script.async = true;
			script.dataset.acuCheckout = 'true';
			script.onload = function () {
				if ( window.FS && window.FS.Checkout ) {
					resolve();
					return;
				}

				reject( new Error( 'checkout_unavailable' ) );
			};
			script.onerror = function () {
				reject( new Error( 'checkout_unavailable' ) );
			};
			document.head.appendChild( script );
		} );

		return checkoutLoader;
	}

	function initSupportCheckout() {
		var config = window.acuAdmin && window.acuAdmin.support;
		var launcher = document.querySelector( '[data-acu-support-open]' );
		var panel = document.getElementById( 'acu-support-panel' );
		var closeButton = document.querySelector( '[data-acu-support-close]' );
		var planButtons = Array.prototype.slice.call( document.querySelectorAll( '[data-acu-support-plan]' ) );
		var message = document.getElementById( 'acu-support-message' );
		var returnFocus;

		if ( ! config || ! launcher || ! panel || ! closeButton || ! planButtons.length ) {
			return;
		}

		function setMessage( text, isError ) {
			if ( ! message ) {
				return;
			}

			message.textContent = text;
			message.classList.toggle( 'is-error', Boolean( isError ) );
		}

		function resetPlans() {
			planButtons.forEach( function ( button ) {
				button.disabled = false;
				button.classList.remove( 'is-loading' );
			} );
		}

		function openPanel() {
			returnFocus = document.activeElement;
			panel.hidden = false;
			panel.setAttribute( 'aria-hidden', 'false' );
			document.body.classList.add( 'acu-support-open' );
			window.requestAnimationFrame( function () {
				closeButton.focus();
			} );
		}

		function closePanel() {
			panel.hidden = true;
			panel.setAttribute( 'aria-hidden', 'true' );
			document.body.classList.remove( 'acu-support-open' );
			setMessage( '', false );

			if ( returnFocus && document.contains( returnFocus ) ) {
				returnFocus.focus();
			}
		}

		launcher.addEventListener( 'click', openPanel );
		closeButton.addEventListener( 'click', closePanel );

		document.addEventListener( 'keydown', function ( event ) {
			var focusable;
			var first;
			var last;

			if ( panel.hidden ) {
				return;
			}

			if ( 'Escape' === event.key ) {
				event.preventDefault();
				closePanel();
				return;
			}

			if ( 'Tab' !== event.key ) {
				return;
			}

			focusable = [ closeButton ].concat( planButtons.filter( function ( button ) {
				return ! button.disabled;
			} ) );
			first = focusable[ 0 ];
			last = focusable[ focusable.length - 1 ];

			if ( event.shiftKey && document.activeElement === first ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && document.activeElement === last ) {
				event.preventDefault();
				first.focus();
			}
		} );

		planButtons.forEach( function ( planButton ) {
			planButton.addEventListener( 'click', function () {
				var planId = planButton.dataset.acuSupportPlan;

				if ( ! planId ) {
					return;
				}

				planButtons.forEach( function ( button ) {
					button.disabled = true;
				} );
				planButton.classList.add( 'is-loading' );
				setMessage( config.loadingLabel, false );

				loadCheckout( config )
					.then( function () {
						var handler = new window.FS.Checkout( {
							product_id: config.productId,
							public_key: config.publicKey
						} );

						handler.open( {
							name: config.productName,
							plan_id: planId,
							licenses: 1,
							billing_cycle: 'lifetime',
							purchaseCompleted: function () {
								setMessage( config.thankYouLabel, false );
							},
							success: function () {
								resetPlans();
								setMessage( config.thankYouLabel, false );
							},
							cancel: function () {
								resetPlans();
								setMessage( '', false );
							}
						} );
					} )
					.catch( function () {
						checkoutLoader = null;
						resetPlans();
						setMessage( config.unavailableLabel, true );
					} );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var form = document.getElementById( 'acu_form' );

		if ( form ) {
			form.addEventListener( 'change', refreshSettingsState );
			form.addEventListener( 'submit', saveWithoutReload );
			refreshSettingsState();
		}

		initSupportCheckout();
	} );
}() );
