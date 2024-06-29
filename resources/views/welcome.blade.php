<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{__('medreg.labels.title')}}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('fonts/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Contact-Form-v2-Modal--Full-with-Google-Map.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2/dist/css/select2-bootstrap.css') }}">
</head>

<body>
    <div>
        <div class="container-fluid">
            <div><h3><img src="{{ asset('images/logo.png') }}" alt="" height="50px" width="50px"> {{__('medreg.labels.title')}}</h3></div>
            <hr>
            <form action="{{ Route('Medical.Register') }}" method="post" id="contactForm">
                {{ csrf_field() }}
                <div class="form-row">
                    <div class="col-12 col-md-6">
                        <div id="successfail">
                            <!-- Display message -->
                            @include('flash::message')      
                            <!-- End display message -->

                            @if ($errors->any())
                            <!-- Display error -->
                                <div class="alert alert-danger">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                </div>
                            <!-- End display error -->
                            @endif
                            
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="col-12 col-md-6" id="message">
                        <h2 class="h4"><i class="fa fa-envelope"></i> {{__('medreg.labels.main-info')}}<small><small class="required-input">&nbsp;{{__('medreg.labels.require')}}</small></small>
                        </h2>

                        <!-- Begin Main Information -->
                        <div class="form-row">
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-name">{{__('medreg.labels.name')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-user-o"></i></span></div>
                                        <input class="form-control" type="text" name="name" required="" placeholder="{{__('medreg.labels.name')}}" id="from-name" value="{{old('name')}}">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sexual -->
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-sexual">{{__('medreg.labels.sexual')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-user-o"></i></span></div>
                                        <select class="form-control" name="sexual" id="from-sexual" required="">
                                            <option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>
                                            <option value="1" {{old('sexual') == '1' ? 'selected':''}}>{{__('medreg.labels.gender.man')}}</option>
                                            <option value="0" {{old('sexual') == '0' ? 'selected':''}}>{{__('medreg.labels.gender.woman')}}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- End Sexual -->

                            <!-- Birthday -->
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-birthday">{{__('medreg.labels.birthday')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-clock-o"></i></span></div>
                                        <input class="form-control" type="date" name="birthday" required="" placeholder="{{__('medreg.labels.birthday')}}" id="from-birthday" value="{{old('birthday')}}">
                                    </div>
                                </div>
                            </div>
                            <!-- End Birthday -->
                        </div>
                        <!-- End Main Information -->

                        <!-- Begin Address -->
                        <div class="form-row">
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-city">{{__('medreg.labels.city')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <select class="form-control" name="city" id="from-city" required="" value="{{old('city')}}">
                                            <option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>
                                            @if(isset($Cities))
                                                @foreach($Cities as $key => $value)
                                                    <option value="{{ $value->id }}" {{old('city') == $value->id ? 'selected':''}}>{{ $value->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-district">{{__('medreg.labels.district')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <select class="form-control" name="district" id="from-district" required="" value="{{old('district')}}">
                                        
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-wards">{{__('medreg.labels.ward')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <select class="form-control" name="ward" id="from-wards" required="" value="{{old('ward')}}">
                                        
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Address -->
       
                        <div class="form-row">
                            <!-- Begin Email address -->
                            <div class="col-12 col-sm-8 col-md-12 col-lg-8">
                                <div class="form-group"><label for="from-email">{{__('medreg.labels.email')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-envelope-o"></i></span></div>
                                        <input class="form-control" type="email" name="email" required="" placeholder="{{__('medreg.labels.email')}}" id="from-email" value="{{old('email')}}">
                                    </div>
                                </div>
                            </div>
                            <!-- End Email address -->

                            <!-- Telephone -->
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-phone">{{__('medreg.labels.tel')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-phone"></i></span></div>
                                        <input class="form-control" type="text" name="phone" required="" placeholder="{{__('medreg.labels.tel')}}" id="from-phone" value="{{old('phone')}}">
                                    </div>
                                </div>
                            </div>
                            <!-- End Telephone -->
                        </div>
                        

                        <div class="form-row">
                            <!-- Healthcare date -->
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-healthcareday">{{__('medreg.labels.healthcaredate')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-clock-o"></i></span></div>
                                        <input class="form-control" type="date" name="healthcaredate" required="" placeholder="{{__('medreg.labels.healthcaredate')}}" id="from-healthcareday" value="{{old('healthcaredate')}}">
                                    </div>
                                </div>
                            </div>
                            <!-- End Healthcare date -->

                            <!-- Healthcare time -->
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group"><label for="from-calltime">{{__('medreg.labels.healthcaretime')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-clock-o"></i></span></div>
                                        <select class="form-control" name="healthcaretime" required="" id="from-calltime">
                                            <option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>
                                            <option value="1" {{old('healthcaretime') == '1' ? 'selected':''}}>{{__("medreg.labels.session-time.morning")}}</option>
                                            <option value="2" {{old('healthcaretime') == '2' ? 'selected':''}}>{{__("medreg.labels.session-time.afternoon")}}</option>
                                            <option value="3" {{old('healthcaretime') == '3' ? 'selected':''}}>{{__("medreg.labels.session-time.overtime")}}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- End healthcare time -->

                            <!-- Clinic -->
                            <div class="col-12 col-sm-4 col-md-12 col-lg-4">
                                <div class="form-group">
                                    <label for="from-clinic">{{__('medreg.labels.clinic')}}</label><span class="required-input">*</span>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-stethoscope"></i></span></div>
                                        <select class="form-control" name="clinic" required="" id="from-clinic">
                                            <option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>
                                            @if(isset($Clinics))
                                                @foreach($Clinics as $key => $value)
                                                    <option value="{{ $value->id }}" {{ old('clinic') == $value->id ? 'selected' : '' }}>
                                                        {{ $value->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <!-- End Clinic -->

                        </div>
              
                        <div class="form-row">
                            <!-- Begin symptom -->
                            @if(isset($Symptom))
                            <div class="col-12 col-sm-12 col-md-12 col-lg-12">
                                <div class="form-group">
                                    <label for="from-symptom">{{__('medreg.labels.symptom')}}</label><span class="required-input">*</span>
                                    <select class="form-control" name="symptom[]" required="" id="from-symptom" multiple="multiple">
                                            
                                            @foreach($Symptom as $key => $value)
                                                <option value="{{ $value->id }}" {{ (collect(old('symptom'))->contains($value->id)) ? 'selected':'' }}>
                                                    {{ $value->name }}
                                                </option>
                                            @endforeach
                                        
                                    </select>
                                </div>
                            </div>
                            @endif
                            <!-- End symptom -->
                        </div>

                        <!-- Begin reCaptcha -->
                        <div class="form-group">
                            <div class="g-recaptcha" data-sitekey="{{env('GOOGLE_RECAPTCHA_KEY', '')}}" data-callback="YourOnSubmitFn"></div>
                        </div>
                        <!-- End reCaptcha -->

                        <!-- Button -->
                        <div class="form-group">
                            <div class="form-row">
                                <div class="col"><button class="btn btn-primary btn-block" type="reset"><i class="fa fa-undo"></i> {{__('medreg.labels.reset')}}</button></div>
                                <div class="col"><button class="btn btn-primary btn-block" type="submit" id="btnSubmit">{{__('medreg.labels.submit')}} <i class="fa fa-chevron-circle-right"></i></button></div>
                            </div>
                        </div>
                        <!-- End button -->

                        <hr class="d-flex d-md-none">
                    </div>

                    <!-- Begin Our Information -->
                    <div class="col-12 col-md-6">
                        <h2 class="h4"><i class="fa fa-location-arrow"></i>&nbsp;{{__('medreg.labels.our-info.address')}}</h2>
                        <div class="form-row">

                            <!-- Begin Google map -->
                            <div class="col-12">
                                <div class="static-map">
                                    <a href="{{__('medreg.labels.our-map.static-map')}}"
                                        target="_blank" rel="noopener">
                                        <iframe src="{{__('medreg.labels.our-map.embed-map')}}" width="100%" height="400" frameborder="0" style="border:0" allowfullscreen>
                                        </iframe>
                                    </a>
                                </div>
                            </div>
                            <!-- End Google map -->
                            
                            <div class="col-sm-6 col-md-12 col-lg-6">
                                <h2 class="h4"><i class="fa fa-user"></i> {{__('medreg.labels.our-info.contact')}}</h2>
                                <div><span><strong>{{__('medreg.labels.our-info.email')}}</strong></span></div>
                                <div><span>{{__('medreg.labels.our-info.email-address')}}</span></div>
                                <div><span><strong>{{__('medreg.labels.our-info.website')}}</strong></span></div>
                                <div><span><a href="{{__('medreg.labels.our-info.website-address')}}" target="_blank">{{__('medreg.labels.our-info.website-address')}}</a></span></div>
                                <hr class="d-sm-none d-md-block d-lg-none">
                            </div>
                            <div class="col-sm-6 col-md-12 col-lg-6">
                                <h2 class="h4"><i class="fa fa-location-arrow"></i>&nbsp;{{__('medreg.labels.our-info.address')}}</h2>
                                <div><span><strong>{{__('medreg.labels.our-info.basis1')}}</strong></span></div>
                                <div><span>{{__('medreg.labels.our-info.basis-address1')}}</span></div>
                                <div><span><strong>{{__('medreg.labels.our-info.basis2')}}</strong></span></div>
                                <div><span>{{__('medreg.labels.our-info.basis-address2')}}</span></div>
                                <div><abbr data-toggle="tooltip" data-placement="top" title="{{__('medreg.labels.our-info.hotline-number')}}">{{__('medreg.labels.our-info.hotline')}}</abbr> {{__('medreg.labels.our-info.hotline-number')}}</div>
                                <hr class="d-sm-none">
                            </div>
                        </div>
                    </div>
                    <!-- End Our information -->

                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/Contact-Form-v2-Modal--Full-with-Google-Map.js') }}"></script>
    <script src="{{ asset('vendor/select2/dist/js/select2.js') }}"></script>
    <script src='https://www.google.com/recaptcha/api.js'></script>
</body>

</html>
<script type="text/javascript">
    function YourOnSubmitFn()
    {
        $('#btnSubmit').prop('disabled', false);
    };

    $(document).ready(function()
    {
        /* Disable button submit */
        $('#btnSubmit').prop('disabled', true);
        /* End button submit */

        $('#from-city').on('change', function(event) {
            event.preventDefault();
            /* Act on the event */
            $.ajax({
                url: '{{ Route("get-district-by-city") }}',
                type: 'GET',
                data: 
                {
                    'City': $('#from-city').val()
                },
            })
            .done(function(result) {
                $('#from-district').empty();
                $('#from-district').append('<option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>');
                $('#from-wards').empty();
                $('#from-wards').append('<option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>');
                $(result).each(function(index, el) {
                    $('#from-district').append('<option value="' + el.id + '">' + el.name + '</option>')
                });
                console.log('done');
            })
            .fail(function() {
                console.log("error");
            })
            .always(function() {
                console.log("complete");
            });
            
        });

        $('#from-district').on('change', function(event) {
            event.preventDefault();
            /* Act on the event */
            $.ajax({
                url: '{{ Route("get-ward-by-district") }}',
                type: 'GET',
                data: 
                {
                    'District': $('#from-district').val()
                },
            })
            .done(function(result) {
                $('#from-wards').empty();
                $('#from-wards').append('<option value="" disabled selected hidden>{{__("medreg.labels.please-choose")}}</option>');
                $(result).each(function(index, el) {
                    $('#from-wards').append('<option value="' + el.id + '">' + el.name + '</option>')
                });
                console.log('done');
            })
            .fail(function() {
                console.log("error");
            })
            .always(function() {
                console.log("complete");
            });
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function(){
        $('#from-symptom').select2({ 
            placeholder: '{{__("medreg.labels.please-choose")}}',
            width: '100%', 
            theme: "bootstrap",
        });
        $('#from-city').select2({ 
            placeholder: '{{__("medreg.labels.please-choose")}}',
            width: '100%',
            theme: "bootstrap",
        });
        $('#from-district').select2({ 
            placeholder: '{{__("medreg.labels.please-choose")}}',
            width: '100%',
            theme: "bootstrap",
        });
        $('#from-wards').select2({ 
            placeholder: '{{__("medreg.labels.please-choose")}}',
            width: '100%',
            theme: "bootstrap",
        });
    });
</script>

<script>
    $('#flash-overlay-modal').modal();
</script>