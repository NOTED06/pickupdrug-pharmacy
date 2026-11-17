

(function($) {
    'use strict';
    
    
    var defaults = {
        debounceDelay: 150,
        minChars: 1,
        highlightMatches: true,
        highlightClass: 'search-highlight',
        noResultsText: 'No matches found',
        showMatchCount: true,
        caseSensitive: false
    };
    
    
    var instances = {};
    
    
    window.initLiveSearch = function(searchInput, table, options) {
        var $input = $(searchInput);
        var $table = $(table);
        
        if($input.length === 0 || $table.length === 0) {
            console.warn('Live search: Input or table not found');
            return null;
        }
        
        var settings = $.extend({}, defaults, options);
        var $tbody = $table.find('tbody');
        var $rows = $tbody.find('tr');
        var searchTimer;
        var instanceId = 'ls_' + Math.random().toString(36).substr(2, 9);
        
        
        var $wrapper = $input.closest('.input-group');
        if($wrapper.length === 0) {
            $input.wrap('<div class="input-group input-group-sm live-search-wrapper"></div>');
            $wrapper = $input.parent();
        }
        
        
        if($wrapper.find('.input-group-prepend').length === 0) {
            $wrapper.prepend('<div class="input-group-prepend"><span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span></div>');
            $input.addClass('border-left-0');
        }
        
        
        var $clearBtn = $wrapper.find('.live-search-clear');
        if($clearBtn.length === 0) {
            $wrapper.find('.input-group-append').remove(); 
            $wrapper.append('<div class="input-group-append"><button type="button" class="btn btn-outline-secondary live-search-clear" style="display:none;"><i class="fas fa-times"></i></button></div>');
            $clearBtn = $wrapper.find('.live-search-clear');
        }
        
        
        var $hint = $wrapper.siblings('.live-search-hint');
        if($hint.length === 0) {
            $wrapper.after('<small class="live-search-hint text-muted d-block" style="font-size:0.75rem;min-height:1.2em;"></small>');
            $hint = $wrapper.siblings('.live-search-hint');
        }
        
        
        var $badge = $table.closest('.card').find('.badge-secondary, .total-badge, [data-live-count]').first();
        var originalCount = $rows.length;
        
        
        function performSearch(query) {
            query = settings.caseSensitive ? query.trim() : query.toLowerCase().trim();
            var matchCount = 0;
            
            
            if(settings.highlightMatches) {
                $tbody.find('.' + settings.highlightClass).each(function() {
                    var $el = $(this);
                    $el.replaceWith($el.text());
                });
            }
            
            if(query.length < settings.minChars) {
                $rows.show();
                $hint.html('');
                if($badge.length) $badge.text(originalCount + ' total');
                return;
            }
            
            $rows.each(function() {
                var $row = $(this);
                var rowText = settings.caseSensitive ? $row.text() : $row.text().toLowerCase();
                var isMatch = rowText.indexOf(query) > -1;
                
                if(isMatch) {
                    $row.show();
                    matchCount++;
                    
                    
                    if(settings.highlightMatches && query.length >= 2) {
                        highlightRow($row, query);
                    }
                } else {
                    $row.hide();
                }
            });
            
            
            if(settings.showMatchCount) {
                if(matchCount === 0) {
                    $hint.html('<span class="text-danger"><i class="fas fa-exclamation-circle"></i> ' + settings.noResultsText + '</span>');
                } else {
                    $hint.html('<span class="text-success"><i class="fas fa-check-circle"></i> ' + matchCount + ' match' + (matchCount !== 1 ? 'es' : '') + ' found</span>');
                }
            }
            
            if($badge.length) {
                $badge.text(matchCount + ' records');
            }
        }
        
        
        function highlightRow($row, query) {
            $row.find('td').each(function() {
                var $cell = $(this);
                
                
                if($cell.find('button, input, img, a, .dropdown').length > 0) return;
                
                var html = $cell.html();
                var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                var newHtml = html.replace(regex, '<span class="' + settings.highlightClass + '">$1</span>');
                
                if(newHtml !== html) {
                    $cell.html(newHtml);
                }
            });
        }
        
        function escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        
        $input.on('keyup.liveSearch input.liveSearch', function(e) {
            clearTimeout(searchTimer);
            var query = $(this).val();
            
            $clearBtn.toggle(query.length > 0);
            
            searchTimer = setTimeout(function() {
                performSearch(query);
            }, settings.debounceDelay);
        });
        
        $clearBtn.on('click.liveSearch', function() {
            $input.val('').trigger('keyup').focus();
        });
        
        
        $input.on('keypress.liveSearch', function(e) {
            if(e.which === 13) {
                e.preventDefault();
                return false;
            }
        });
        
        
        if($input.val().length > 0) {
            setTimeout(function() {
                $input.trigger('keyup');
            }, 100);
        }
        
        
        instances[instanceId] = {
            input: $input,
            table: $table,
            settings: settings,
            destroy: function() {
                $input.off('.liveSearch');
                $clearBtn.off('.liveSearch');
                $hint.remove();
                delete instances[instanceId];
            }
        };
        
        return instances[instanceId];
    };
    
    
    $(document).ready(function() {
        
        $('.card').each(function() {
            var $card = $(this);
            var $table = $card.find('table').first();
            var $searchInput = $card.find('input[name="search"], input[placeholder*="Search"], input[placeholder*="search"]').first();
            
            
            if($table.length === 0 || $searchInput.length === 0) return;
            if($searchInput.hasClass('live-search-initialized')) return;
            if($searchInput.attr('id') === 'liveSearch') return; 
            
            
            $searchInput.addClass('live-search-initialized');
            
            
            var $form = $searchInput.closest('form');
            if($form.length > 0) {
                $form.on('submit', function(e) {
                    e.preventDefault();
                    return false;
                });
            }
            
            
            initLiveSearch($searchInput, $table);
        });
    });
    
})(jQuery);
