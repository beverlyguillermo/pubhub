var topicsPage = (function ($, hubster, lazy) {

    return {

        /**
         * Hash of tag filters designated by the hub mananger. The
         * hash is populated with each tag's name, slug, and a count
         * of how many articles pulled in from the Pages model 
         * are contributing to each tag.
         * @type {Object}
         */
        tags: {},

        /**
         * The number of articles that must be in each tag before
         * the async calls stop.
         * @type {Number}
         */
        tagQuota: 5,

        /**
         * The API endpoint to be queried for more articles.
         * @type {String}
         */
        endpoint: $(".page-container").attr("data-endpoint"),

        /**
         * IDs to exclude from the API call (articles in the
         * feature ares of the page)
         * @type {string} Comma separated list of IDs
         */
        excluded_ids: $(".page-container").attr("data-excluded-ids"),

        /**
         * Page of API to query
         * @type {Number}
         */
        page: 1,

        /**
         * Number of articles to get per API query. This must
         * match the number of articles pulled from the Pages
         * model.
         * @type {Number}
         */
        per_page: 25,

        /**
         * Isotope container div
         * @type {[type]}
         */
        container: $('.content-col'),

        /**
         * Initialize isotope and start loading more articles.
         * @param  {object} tags
         * @return null
         */
        init: function (tags) {
            topicsPage.tags = tags;
            hubster.convertDates();

            $(".filters-container .display-nav").on("click", function (e) {

                var container = $(this).parents(".filters-container");

                // do not close if the filters are open and the icon was not clicked on
                if (container.hasClass("open") && !$(e.target).is("i")) {
                    return;
                }

                container.find(".filters").toggleClass("open closed").toggle(500, "easeOutQuart");
                container.toggleClass("open closed");
            });

            // display filters for people who have JS
            $(".filters-container")
                .animate({ opacity: 1 }, 1000);

            topicsPage.container.imagesLoaded( function(){

                topicsPage.container.isotope({
                    layoutMode: "masonry",
                    itemSelector: '.article',
                    getSortData: {
                        pubDate: function ( $elem ) {
                            return $elem.attr('data-pub');
                        },
                        popularity: function ( $elem ) {
                            return $elem.attr('data-pop');
                        }
                    },
                    sortBy: 'pubDate',
                    sortAscending : false
                });

                $('.filters-col .filters a').on("click", function (e) {

                    $('.filters-col .filters a').each(function() {
                        $(this).removeClass("selected");
                    });
                    $(this).toggleClass("selected");
                    
                    var selector = $(this).attr("data-filter");
                    topicsPage.container.isotope({ filter: selector });

                    return false;
                });

                topicsPage.loadMore();

            });
        },
        /**
         * Load more articles until the tag quota is reached.
         * @return null
         */
        loadMore: function() {

            // Check if we should get more articles
            if (topicsPage.tagQuotaMet()) {
                return;
            }

            // Increase the page number to query by one
            topicsPage.page = topicsPage.page + 1;

            var response = hubJS.get(topicsPage.endpoint, {
                per_page: topicsPage.per_page,
                page: topicsPage.page,
                excluded_ids: topicsPage.excluded_ids
            });

            // When the response comes back...
            response.then(function (payload) {

                // Update tag count
                topicsPage.updateTagCount(payload);

                var data = { data: payload._embedded.articles };

                // Render articles in the template
                var newItems = $(lazy.compileTemplate(data, "template-topic-page-articles"));

                // Add the items on the page via isotope
                newItems.imagesLoaded(function(){
                    topicsPage.container.isotope("insert", newItems);
                });
                
                // Get more articles if we haven't met the tag quota AND there are more articles to get
                if (payload._links.next && !topicsPage.tagQuotaMet()) {
                    topicsPage.loadMore();
                }
            });
        },
        /**
         * Checks if the tag quotoa has been met IF there were
         * tag filters added to this topic page in the manager.
         * @return {boolean} TRUE if tag quotoa has been met; FALSE if not.
         */
        tagQuotaMet: function()
        {
            var quotaMet = true;
            if (topicsPage.tags) {
                $.each(topicsPage.tags, function (index, value) {
                    if (value.count < topicsPage.tagQuota) {
                        quotaMet = false;
                        return;
                    }
                });
            }
            
            return quotaMet;
        },
        /**
         * Loop through the articles brought back from the async
         * call and update the tags object with how many of these
         * articles conrtibuted to which tags.
         * @param  {object} payload API payload
         * @return null
         */
        updateTagCount: function(payload) {
            $.each(payload._embedded.articles, function(index, article) {
                if (article._embedded.tags) {
                    $.each(article._embedded.tags, function(index, tag) {
                        if (topicsPage.tags[tag.id]) {
                            topicsPage.tags[tag.id]["count"] = topicsPage.tags[tag.id]["count"] + 1;
                        }
                    });
                }
            });
        }
    }
})(jQuery, hubster, lazy);