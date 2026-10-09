<!DOCTYPE html>
@include('layouts.lang')
<head>
   @stack('linkstack-head')
   @stack('linkstack-head-end')
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