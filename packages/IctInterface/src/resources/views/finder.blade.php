<script>
    $("document").ready(function() {

        var url = null;
        var fieldComposer = null;
        // $(".finder").keyup(function(e) {
        $(document).on('focus', '.finder', function(e) {
            var fieldId = e.currentTarget.id;

            if(fieldId.match("\\[")) {
                var pos = fieldId.indexOf("[");
                fieldComposer = fieldId.substr(0, pos);
            } else {
                fieldComposer = fieldId;
            }
console.log("fieldComposer", fieldComposer)
console.log($("input[id='" + fieldId+"'].finder").data('route'))
            if ($("#serp-" + fieldComposer).length > 0) {
                $("#serp-" + fieldComposer).remove();
            } else {
                @if(!request()->has('report'))
                    var request = {
                            'route': $("input[id='" + fieldId+"'].finder").data('route'),

                            };
                @else
                    var request = {
                            'route': $("input[id='" + fieldId+"'].finder").data('route'),
                            'report': {{ request('report') }},
  
                        };
                @endif

                $.ajax('{{ route('get.finder.route') }}', {
                    method: 'GET',
                    data: request,
                    complete: function(response) {
                        let resp = response.responseJSON
                        if (resp.result == 'success') {
                            return url = resp.route;
                        }
                    }
                });

            }
        });
        
        $(document).on('keyup', '.finder', function(e) {
            var fieldId = e.currentTarget.id;

            if ($("input[id='" + fieldId+"'].finder").val().length > 2) {
                $('.serp-finder').remove();
                if($(".serp-div_prop").length == 0) {
                    var finder = $(".serp-finder-base").clone().attr({
                        "id": "serp-" + fieldComposer
                    }).addClass("serp-finder serp-div-prop").removeClass('serp-finder-base');
                } else {
                    var finder = $(".serp-finder");
                    finder.empty();
                }
                

                finder.appendTo($("input[id='" + fieldId+"'].finder").parent()).show();

                @if(!request()->has('report'))
                    var request = {
                                'query': $("input[id='" + fieldId+"'].finder").val(),
                                'fieldId': fieldId,
                            };
                @else
                    var request = {
                            'query': $("input[id='" + fieldId+"'].finder").val(),
                            'report': {{ request('report') }},
                            'fieldId': fieldId,
                        };
                @endif

console.log('call',url)
                $.ajax(url, {
                    method: 'GET',
                    data: request,
                    complete: function(response) {
                        var resp = response.responseJSON;
                        var serp = '';
                        if (resp.result == 'success') {
                            finder.html('');

                            $.each(resp.html, function(index, value) {
                                serp += value + "<br>\n";
                            });

                            $("#serp-"+fieldComposer).html(serp);

                        } else {
                            alert(resp.message);
                        }
                    }
                });
            }

            $(".card").click(function() {
                $(".serp-finder").remove();
            })
        });
    });
</script>

<div class="serp-finder-base"></div>
