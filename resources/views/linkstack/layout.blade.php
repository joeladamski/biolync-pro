<!DOCTYPE html>
@include('layouts.lang')
<head>
   @stack('linkstack-head')
   @stack('linkstack-head-end')
@if(request()->boolean('spotlight_preview'))
   <style id="spotlight-scrollbar">
   :root {color-scheme:dark;scrollbar-color:#50525e #181820;scrollbar-width:thin;}
   ::-webkit-scrollbar {width:8px;height:8px;}
   ::-webkit-scrollbar-track {background:#181820;}
   ::-webkit-scrollbar-thumb {background:#50525e;border-radius:8px;border:2px solid #181820;}
   </style>
   @endif
</head>
<body>
   @stack('linkstack-body-start')
   <div class="container biolync-profile">
      <div class="row">
         <div class="column biolync-profile-column">
            @stack('linkstack-content')
         </div>
      </div>
   </div>
   @stack('linkstack-body-end')
</body>
</html>