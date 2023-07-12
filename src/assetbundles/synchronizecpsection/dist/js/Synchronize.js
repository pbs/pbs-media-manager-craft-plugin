/**
 * Media Manager
 *
 * @package       PaperTiger:MediaManager
 * @author        Paper Tiger
 * @copyright     Copyright (c) 2020 Paper Tiger
 * @link          https://www.papertiger.com/
 */
(function($) {
    /** global: Craft */
    /** global: Garnish */
    Craft.SynchronizeAdmin = Garnish.Base.extend(
        {
            init: function() {
            	var self = this;

                this.addListener($('#syncrhonizeshowbtn'), 'activate', 'synchronizeShow');
                this.addListener($('#syncrhonizesinglebtn'), 'activate', 'synchronizeSingle');
                this.addListener($('#syncrhonizeallbtn'), 'activate', 'synchronizeAll');
                this.addListener($('#syncrhonizeshowentriesbtn'), 'activate', 'synchronizeShowEntries');
                this.addListener($('#addshowsite'), 'activate', 'addShowSite');
                this.addListener($('#cleanallbtn'), 'activate', 'cleanGarbageEntries');

                // Add Remove Button to Select
                $( '#siteId-field .select' ).after( '&nbsp;<div class="deletesite btn delete icon"></div>' );
                $( 'body' ).on( 'click', '.deletesite', function() {
                    self.deleteShowSite( $( this ).parents( '#siteId-field' ) );
                })
            },

            addShowSite: function() {

                var siteField     = $( '#siteId-field' )
                var clone         = siteField.clone()
                var modifiedClone = $( clone ).find( '.heading' ).remove().end()

                $( '#addshowsite' ).before( $( modifiedClone ).find( 'select' ).val( 1 ).end() )

            },

            deleteShowSite: function( target ) {
                
                if( $( 'select[name="siteId[]"]' ).length > 1 ) {
                    
                    target.remove();
                    return
                }

                Craft.cp.displayError(Craft.t('mediamanager', 'You need at least one associated site.') );

            },

            cleanGarbageEntries: function() {

                $( '#cleanallbtn' ).addClass( 'disabled' );

                Craft.postActionRequest('mediamanager/synchronize/run-clean', [], $.proxy(function(response, textStatus) {

                    if (textStatus === 'success') {
                        if (response.success) {
                            Craft.cp.displayNotice(Craft.t('mediamanager', 'Start cleaning '+ response.total +' entries.'));
                            setTimeout( function() {
                            	location.href = Craft.getUrl( 'mediamanager/entries/');
                            }, 1000);
                        }
                        else if (response.errors) {
                        	$( '#cleanallbtn' ).removeClass( 'disabled' );
                            var errors = this.flattenErrors(response.errors);
                            Craft.cp.displayError(Craft.t('mediamanager', 'Could not start cleaning:') + "\n\n" + errors.join("\n") );
                        }
                        else {
                        	$( '#cleanallbtn' ).removeClass( 'disabled' );
                            Craft.cp.displayError();
                        }
                    } else {
                    	$( '#cleanallbtn' ).removeClass( 'disabled' );
                    }

                }, this));

            },

            synchronizeShow: function() {

            	var showId   = $( '#showId' ).val()
            	var name     = $( '#name' ).val()
                var forceRegenerateThumbnail = $( '#forceRegenerateThumbnail' ).prop( 'checked' )

        		if( showId ) {
                    
                    var data = {
                        showId: showId,
                        forceRegenerateThumbnail: forceRegenerateThumbnail
                    };

                    $( '#syncrhonizeshowbtn' ).addClass( 'disabled' );

                    Craft.postActionRequest('mediamanager/synchronize/synchronize-show', data, $.proxy(function(response, textStatus) {

                        if (textStatus === 'success') {
                            if (response.success) {
                                Craft.cp.displayNotice(Craft.t('mediamanager', 'Synchronize "'+ name +'" started.'));
                                setTimeout( function() {
                                	location.href = Craft.getUrl( 'mediamanager/entries/');
                                }, 1000);
                            }
                            else if (response.errors) {
                            	$( '#syncrhonizeshowbtn' ).removeClass( 'disabled' );
                                var errors = this.flattenErrors(response.errors);
                                Craft.cp.displayError(Craft.t('mediamanager', 'Could not start synchronize:') + "\n\n" + errors.join("\n") );
                            }
                            else {
                            	$( '#syncrhonizeshowbtn' ).removeClass( 'disabled' );
                                Craft.cp.displayError();
                            }
                        } else {
                        	$( '#syncrhonizeshowbtn' ).removeClass( 'disabled' );
                        }

                    }, this));

        		} else {

        			if( !showId ) {
	        			Craft.cp.displayError(Craft.t('mediamanager', 'Something wrong, please reload the page'));
        				return
        			}

        			Craft.cp.displayError(Craft.t('mediamanager', 'Site ID is required'));
        		}
            },

            synchronizeSingle: function() {

            	var apiKey   = $( '#apiKey' ).val()
                var siteId   = []
                var forceRegenerateThumbnail = $( '#forceRegenerateThumbnail' ).prop( 'checked' )

                $( 'select[name="siteId[]"]' ).each( function() {

                    var thisSiteId = $( this ).val()

                    if( siteId.indexOf( thisSiteId ) === -1 ) {
                        siteId.push( thisSiteId );
                    }
                })

        		if( apiKey && siteId ) {
                    
                    var data = {
                        apiKey: apiKey,
                        siteId: siteId,
                        forceRegenerateThumbnail: forceRegenerateThumbnail
                    };

                    $( '#syncrhonizesinglebtn' ).addClass( 'disabled' );

                    Craft.postActionRequest('mediamanager/synchronize/synchronize-single', data, $.proxy(function(response, textStatus) {

                        if (textStatus === 'success') {
                            if (response.success) {
                                Craft.cp.displayNotice(Craft.t('mediamanager', 'Synchronize started.'));
                                setTimeout( function() {
                                	location.href = Craft.getUrl( 'mediamanager/entries/');
                                }, 1000);
                            }
                            else if (response.errors) {
                            	$( '#syncrhonizesinglebtn' ).removeClass( 'disabled' );
                                var errors = this.flattenErrors(response.errors);
                                Craft.cp.displayError(Craft.t('mediamanager', 'Could not start synchronize:') + "\n\n" + errors.join("\n") );
                            }
                            else {
                            	$( '#syncrhonizesinglebtn' ).removeClass( 'disabled' );
                                Craft.cp.displayError();
                            }
                        } else {
                        	$( '#syncrhonizesinglebtn' ).removeClass( 'disabled' );
                        }

                    }, this));

        		} else {
        			Craft.cp.displayError(Craft.t('mediamanager', 'Media Asset\'s Site and API Key are required'));
        		}
            },

            synchronizeAll: function() {

                $( '#syncrhonizeallbtn' ).addClass( 'disabled' );

                var forceRegenerateThumbnail = $( '#forceRegenerateThumbnail' ).prop( 'checked' )

                Craft.postActionRequest('mediamanager/synchronize/synchronize-all?forceRegenerateThumbnail=' + forceRegenerateThumbnail, {}, $.proxy(function(response, textStatus) {

                    if (textStatus === 'success') {
                        if (response.success) {
                            Craft.cp.displayNotice(Craft.t('mediamanager', 'Synchronize for all show started.'));
                            setTimeout( function() {
                            	location.href = Craft.getUrl( 'mediamanager/entries/');
                            }, 1000);
                        }
                        else if (response.errors) {
                        	$( '#syncrhonizeallbtn' ).removeClass( 'disabled' );
                            var errors = this.flattenErrors(response.errors);
                            Craft.cp.displayError(Craft.t('mediamanager', 'Could not start synchronize:') + "\n\n" + errors.join("\n") );
                        }
                        else {
                        	$( '#syncrhonizeallbtn' ).removeClass( 'disabled' );
                            Craft.cp.displayError();
                        }
                    } else {
                    	$( '#syncrhonizeallbtn' ).removeClass( 'disabled' );
                    }

                }, this));
            },

            synchronizeShowEntries: function() {

                $( '#syncrhonizeshowentriesbtn' ).addClass( 'disabled' );

                Craft.postActionRequest('mediamanager/synchronize/synchronize-show-entries', {}, $.proxy(function(response, textStatus) {

                    if (textStatus === 'success') {
                        if (response.success) {
                            Craft.cp.displayNotice(Craft.t('mediamanager', 'Synchronize for show entries started.'));
                            setTimeout( function() {
                                location.href = Craft.getUrl( 'mediamanager/entries/');
                            }, 1000);
                        }
                        else if (response.errors) {
                            $( '#syncrhonizeshowentriesbtn' ).removeClass( 'disabled' );
                            var errors = this.flattenErrors(response.errors);
                            Craft.cp.displayError(Craft.t('mediamanager', 'Could not start synchronize:') + "\n\n" + errors.join("\n") );
                        }
                        else {
                            $( '#syncrhonizeshowentriesbtn' ).removeClass( 'disabled' );
                            Craft.cp.displayError();
                        }
                    } else {
                        $( '#syncrhonizeshowentriesbtn' ).removeClass( 'disabled' );
                    }

                }, this));
            },

            flattenErrors: function(responseErrors) {
                var errors = [];

                for (var attribute in responseErrors) {
                    if (!responseErrors.hasOwnProperty(attribute)) {
                        continue;
                    }

                    errors = errors.concat(responseErrors[attribute]);
                }

                return errors;
            }
    	}
    );
})(jQuery);