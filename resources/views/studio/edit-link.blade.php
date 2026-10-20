@extends('layouts.sidebar')

@include('components.admin-rich-text')

@section('content')

@push('sidebar-stylesheets')
<script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>
@endpush

<div class="container-fluid content-inner py-4 biolync-block-editor">
    <div class="row">   
        
     <div class="col-lg-12">
        <div class="card rounded">
            <div class="card-body">
               <div class="row">
                   <div class="col-sm-12">  
  
                    @push('sidebar-stylesheets')
                    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
                    @endpush
                    
                    <section class='text-gray-400'>
                    <p class="text-muted">Build your page with links, formatted text and headings. Choose a block, edit its content, then save.</p>
                    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                    
                        <h3 class="card-header"><i class="bi bi-journal-plus me-2" aria-hidden="true"></i>@if($LinkID !== 0) {{__('messages.Edit')}} @else {{__('messages.Add')}} @endif {{__('messages.Block')}}</h3>
                    
                        <div class='card-body'>
                            <form action="{{ route('addLink') }}" method="post" id="my-form">
                                @method('POST')
                                @csrf
                                <input type='hidden' name='linkid' value="{{ $LinkID }}" />
                    
                                <div class="form-group col-lg-8 flex justify-around">
                                    <div class="btn-group shadow m-2">
                                        <button type="button" id='btnLinkType' class="btn btn-primary rounded-pill" title='{{__('messages.Click to change link blocks')}}' data-toggle="modal" data-target="#SelectLinkType">{{__('messages.Select Block')}}
                                            <span class="btn-inner">
                                                <i class="bi bi-window-plus"></i>
                                            </span>
                                        </button>{{infoIcon(__('messages.Click for a list of available link blocks'))}}
                                          
                                        <input type='hidden' name='typename' value='{{$typename}}'>
                    
                                    </div>
                                </div>
                    
                                <div id='link_params' class='col-lg-8'></div>
                    
                                <div class="d-flex flex-wrap gap-2 align-items-center pt-4">
                                    <a class="btn btn-danger me-3" href="{{ url('studio/links') }}">{{__('messages.Cancel')}}</a>
                                    <button type="submit" class="btn btn-primary me-3">{{__('messages.Save')}}</button>
                                    <button type="submit" name="param" value="add_more" class="btn btn-soft-primary">{{__('messages.Save and Add More')}}</button>
                                </div>                                
                    
                            </form>
                        </div>
                    </section>
                    <br><br>

                    <!-- Modal -->
                    <style>.modal-title{color:#000!important;}</style>
                    <x-modal title="{{__('messages.Select Block')}}" id="SelectLinkType">
                    
                        <div class="d-flex flex-row  flex-wrap p-3">
                            @foreach ($LinkTypes as $lt)
                            @php 
                            if(block_text_translation_check($lt['title'])) {$title = bt($lt['title']);} else {$title = __('messages.block.title.'.$lt['typename']);}
                            $description = bt($lt['description']) ?? __('messages.block.description.'.$lt['typename']); 
                            @endphp
                            <a href="#" data-dismiss="modal" data-typeid="{{$lt['typename']}}" data-typename="{{$title}}" class="hvr-grow m-2 w-100 d-block doSelectLinkType">
                                <div class="rounded mb-3 shadow-lg">
                                    <div class="row g-0">
                                        <div class="col-auto bg-light d-flex align-items-center justify-content-center p-3">
                                            <i class="{{$lt['icon']}} text-primary h1 mb-0"></i>
                                        </div>
                                        <div class="col">
                                            <div class="card-body">
                                                <h5 class="card-title text-dark mb-0">{{$title}}</h5>
                                                <p class="card-text text-muted">{{$description}}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>      
                            </a>                     
                            @endforeach
                    
                        </div>
                    
                        <x-slot name="buttons">
                            <button type="button" class="btn btn-gray" data-dismiss="modal">{{__('messages.Close')}}</button>
                        </x-slot>
                    
                    </x-modal>
  
                   </div>
               </div>
            </div>
         </div>
        </div>
        
      </div>
    </div>

@endsection

@push("sidebar-scripts")
<script>
$(function() {
    LoadLinkTypeParams($("input[name='typename']").val() , $("input[name=linkid]").val());

    $('.doSelectLinkType').on('click', function() {
        $("input[name='typename']").val($(this).data('typeid'));
        $("#btnLinkType").text($(this).data('typename'));

        LoadLinkTypeParams($(this).data('typeid'), $("input[name=linkid]").val());

        $('#SelectLinkType').modal('hide');
    });


    var pendingForm;
    function LoadLinkTypeParams($TypeId, $LinkId) {
        var baseURL = <?php echo "\"" . url('') . "\""; ?>;
        if (pendingForm) pendingForm.abort();
        window.BioLyncEditors?.destroyWithin(document.getElementById('link_params'));
        const buttons = $('#my-form button[type="submit"]');
        buttons.prop('disabled', true);
        $('#link_params').html('<p role="status">Loading block…</p>');
        pendingForm = $.get(baseURL + `/studio/linkparamform_part/${encodeURIComponent($TypeId)}/${$LinkId}`)
          .done(function (html) {
            $('#link_params').html(html);
            document.dispatchEvent(new Event('contentLoaded'));
            buttons.prop('disabled', false);
          }).fail(function (_, status) {
            if (status !== 'abort') $('#link_params').html('<p class="text-danger" role="alert">Unable to load this block. Select it again to retry.</p>');
          });
    }
});
</script>
@endpush
