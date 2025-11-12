/*!
 * Thread list
 *
 * @deps vue-js, hb-filters-moment, hashboard-rest, hb-components-loading, hb-components-pagination
 */

/*global Vue: false*/





( ($) => {

  'use strict';

  new Vue( {

    el: '#hamethread-list',

    data: {
      threads: [],
      loading: false,
      total: 0,
      current: 1,
      s: '',
      resolved: 0,
      private: false
    },

    mounted(){
      this.update();
    },

    computed: {
      status(){
        return this.private ? 'private' : 'private,publish';
      },
    },

    methods: {
      fetch( page ) {
        this.current = page;
        if ( this.loading ) {
          return;
        }
        this.loading = true;
        const self = this;
        const args = {
          s: this.s,
          page: this.current,
          status: this.status,
          resolved: this.resolved,
        };
        $.hbRest( 'GET', 'hamethread/v1/threads/me', args ).done((response, status, xhr) => {
          self.threads = response;
          self.total = parseInt( xhr.getResponseHeader( 'X-WP-Max-Page' ), 10 );
        }).fail($.hbRestError()).always(()=>{
          self.loading = false;
        });
      },

      update(){
        this.fetch( 1 );
      },

      pageChangeHandler( page ) {
        this.fetch( page );
      }
    }
  } );

})(jQuery);
