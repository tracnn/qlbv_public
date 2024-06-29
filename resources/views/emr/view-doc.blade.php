<!DOCTYPE html>
<html>
<head>
  <title>PDF.js Express Viewer</title>
</head>
<!-- Import PDF.js Express Viewer as a script tag from the lib folder using a relative path -->
<!-- <script src='/lib/webviewer.min.js'></script> -->
<script src="{{ asset('vendor/pdfjs/lib/webviewer.min.js') }}"></script>
<body>
  <div id='viewer' style="width:100%;height:100vh;"></div>

  <script>
    WebViewer({
      path: "{{ asset('vendor/pdfjs/lib') }}", // path to the PDF.js Express'lib' folder on your server
      licenseKey: 'smG1780fC7lGqa6i7if2',
      initialDoc: '{{$aa}}',
      //initialDoc: 'https://pdftron.s3.amazonaws.com/downloads/pl/webviewer-demo.pdf',
      // initialDoc: '/path/to/my/file.pdf',  // You can also use documents on your server
    }, document.getElementById('viewer'))
    .then(instance => {
      // now you can access APIs through the WebViewer instance
      const { Core, UI } = instance;

      // adding an event listener for when a document is loaded
      Core.documentViewer.addEventListener('documentLoaded', () => {
        console.log('document loaded');
      });

      // adding an event listener for when the page number has changed
      Core.documentViewer.addEventListener('pageNumberUpdated', (pageNumber) => {
        console.log(`Page number is: ${pageNumber}`);
      });
    });
  </script>

</body>
</html>