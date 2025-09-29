<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Flipbook Viewer</title>
    
    <!-- Bootstrap CSS (Laravel 5.5 compatible version) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome (Laravel 5.5 compatible version) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- StPageFlip CSS -->
    <link rel="stylesheet" href="https://unpkg.com/page-flip/dist/css/page-flip.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            padding: 20px 0;
        }
        
        .controls-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 8px 15px;
            margin-bottom: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 2px;
            font-size: 16px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 11px;
            margin-bottom: 0;
        }
        
        .btn-custom {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
            font-size: 13px;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            color: white;
        }
        
        .btn-custom:focus {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            color: white;
        }
        
        .status-text {
            color: #7f8c8d;
            font-weight: 500;
            margin-left: 10px;
            font-size: 12px;
        }
        
        .link-custom {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            font-size: 12px;
        }
        
        .link-custom:hover {
            color: #764ba2;
        }
        
        .flipbook-container {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: center;
            height: calc(100vh - 120px);
        }
        
        #flipbook {
            width: 100%;
            max-width: 1000px;
            height: 100%;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-radius: 10px;
            overflow: visible;
            background: #fff;
        }
        
        /* StPageFlip custom styling */
        .page-flip {
            width: 100% !important;
            height: 100% !important;
        }
        
        .page-flip .page {
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .page-flip .page img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        
        /* Đảm bảo PDF không bị cắt */
        .page-flip .page-content {
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        
        /* Turn.js styling */
        #magazine {
            width: 950px;
            height: 700px;
            margin: 0 auto;
        }
        
        #magazine .page {
            background: white;
            border: 1px solid #ccc;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        #magazine .page img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Loading animation for Turn.js */
        .turn-loading {
            background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8ZGVmcz4KICAgIDxsaW5lYXJHcmFkaWVudCBpZD0iZ3JhZGllbnQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjAlIj4KICAgICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6IzMzN2FiNztzdG9wLW9wYWNpdHk6MSIgLz4KICAgICAgPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojMzM3YWI3O3N0b3Atb3BhY2l0eTowIiAvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICA8L2RlZnM+CiAgPHBhdGggZD0iTTIwIDJhMTggMTggMCAwIDEgMTggMThjMCA5Ljk0LTguMDYgMTgtMTggMThTMiAyOS45NCAyIDIwIDEwLjA2IDIgMjAgMnoiIGZpbGw9Im5vbmUiIHN0cm9rZT0idXJsKCNncmFkaWVudCkiIHN0cm9rZS13aWR0aD0iNCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtZGFzaGFycmF5PSIxOCIgc3Ryb2tlLWRhc2hvZmZzZXQ9IjAiPgogICAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJyb3RhdGUiIGZyb209IjAgMjAgMjAiIHRvPSIzNjAgMjAgMjAiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIi8+CiAgPC9wYXRoPgo8L3N2Zz4K') no-repeat center center;
            background-size: 40px 40px;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 18px;
            color: #666;
        }
        
        .loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            #flipbook {
                width: 100%;
                height: 100%;
            }
            
            .main-container {
                padding: 10px;
            }
            
            .controls-section {
                padding: 6px 10px;
                margin-bottom: 8px;
            }
            
            .flipbook-container {
                padding: 8px;
                height: calc(100vh - 100px);
            }
            
            .title {
                font-size: 14px;
            }
            
            .subtitle {
                font-size: 10px;
            }
            
            .btn-custom {
                padding: 4px 8px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid main-container">
        <!-- Controls Section with Title -->
        <div class="controls-section">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="title">
                        <i class="fas fa-book-open mr-2"></i>
                        PDF Flipbook Viewer
    </h1>
                    <p class="subtitle">Xem tài liệu PDF với hiệu ứng lật trang đẹp mắt</p>
                </div>
                <div class="col-md-6">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <button id="btnPrev" class="btn btn-custom mr-2">
                                <i class="fas fa-chevron-left mr-1"></i>
                                Trang trước
                            </button>
                            <button id="btnNext" class="btn btn-custom mr-3">
                                Trang sau
                                <i class="fas fa-chevron-right ml-1"></i>
                            </button>
                            <span id="status" class="status-text">Đang tải...</span>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="{{ $pdfUrl }}" target="_blank" class="link-custom">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Mở viewer thường
                            </a>
                        </div>
                    </div>
                </div>
            </div>
    </div>

        <!-- Flipbook Container -->
        <div class="flipbook-container">
            <div id="flipbook">
                <div class="loading">Đang tải PDF...</div>
    </div>
  </div>
</div>

    <!-- JavaScript Libraries -->
    <!-- jQuery (Laravel 5.5 compatible version) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Bootstrap JS (Laravel 5.5 compatible version) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    
    <!-- PDF.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.14.305/pdf.worker.min.js";
</script>

    <!-- StPageFlip - Correct CDN Sources -->
    <script>
      // Function to load StPageFlip from correct CDN sources
      function loadStPageFlip() {
        var cdnSources = [
          'https://unpkg.com/page-flip/dist/js/page-flip.browser.js',
          'https://cdn.jsdelivr.net/npm/page-flip@1.5.2/dist/js/page-flip.browser.js',
          'https://cdn.skypack.dev/page-flip@1.5.2/dist/js/page-flip.browser.js',
          'https://cdnjs.cloudflare.com/ajax/libs/page-flip/1.5.2/page-flip.browser.js'
        ];
        
        var currentIndex = 0;
        
        function tryLoadFromCDN(index) {
          if (index >= cdnSources.length) {
            console.error('All StPageFlip CDN sources failed');
            return;
          }
          
          console.log('Trying to load StPageFlip from:', cdnSources[index]);
          
          var script = document.createElement('script');
          script.src = cdnSources[index];
          script.onload = function() {
            console.log('StPageFlip script loaded from:', cdnSources[index]);
            // Check if St namespace is available
            setTimeout(function() {
              if (typeof St !== 'undefined' && typeof St.PageFlip !== 'undefined') {
                console.log('StPageFlip is now available:', St.PageFlip);
              } else {
                console.log('StPageFlip loaded but St.PageFlip is still undefined, trying next CDN...');
                tryLoadFromCDN(index + 1);
              }
            }, 500);
          };
          script.onerror = function() {
            console.error('Failed to load from:', cdnSources[index]);
            tryLoadFromCDN(index + 1);
          };
          document.head.appendChild(script);
        }
        
        // Start loading from first CDN
        tryLoadFromCDN(0);
      }
      
      // Load StPageFlip immediately
      loadStPageFlip();
    </script>

<script>
    // jQuery document ready (Laravel 5.5 compatible)
    $(document).ready(function() {
      setTimeout(initFlipbook, 500);
    });

    function initFlipbook() {
      var pdfUrl = "{{ $pdfUrl }}";
      var $flipContainer = $("#flipbook");
      var $statusEl = $("#status");
      
      // Kiểm tra xem StPageFlip đã được tải chưa
      if (typeof St === 'undefined' || typeof St.PageFlip === 'undefined') {
        console.error('StPageFlip library not loaded or St.PageFlip is undefined');
        $statusEl.text('Đang tải thư viện StPageFlip...');
        
        // Thử nhiều lần với khoảng thời gian khác nhau
        var attempts = 0;
        var maxAttempts = 15;
        
        function checkStPageFlip() {
          attempts++;
          console.log('Checking StPageFlip availability, attempt:', attempts);
          console.log('typeof St:', typeof St);
          console.log('typeof St.PageFlip:', typeof St !== 'undefined' ? typeof St.PageFlip : 'St is undefined');
          
          if (typeof St !== 'undefined' && typeof St.PageFlip !== 'undefined') {
            console.log('StPageFlip is now available!', St.PageFlip);
            initFlipbook();
            return;
          }
          
          if (attempts >= maxAttempts) {
            console.error('StPageFlip still not available after', maxAttempts, 'attempts');
            $statusEl.text('StPageFlip không tải được, thử thư viện khác...');
            
            // Thử tải thư viện flipbook khác
            loadAlternativeFlipbook();
            return;
          }
          
          // Đợi thêm 1 giây và thử lại
          setTimeout(checkStPageFlip, 1000);
        }
        
        checkStPageFlip();
        return;
      }
      
      console.log('StPageFlip library loaded successfully');
      
      // Bắt đầu tải PDF
      loadPDFAndCreateFlipbook();
    }

    // Function to load alternative flipbook library
    function loadAlternativeFlipbook() {
      console.log('Loading alternative flipbook library...');
      
      // Thử tải Turn.js (jQuery-based flipbook)
      var turnScript = document.createElement('script');
      turnScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/turn.js/4.1.0/turn.min.js';
      turnScript.onload = function() {
        console.log('Turn.js loaded successfully');
        $statusEl.text('Đã tải Turn.js, khởi tạo flipbook...');
        startTurnJSFlipbook();
      };
      turnScript.onerror = function() {
        console.error('Turn.js failed to load, using simple viewer');
        $statusEl.text('Thư viện flipbook không tải được, sử dụng chế độ đơn giản');
        startSimpleFlipBook();
      };
      document.head.appendChild(turnScript);
    }

    // Function to start Turn.js flipbook
    function startTurnJSFlipbook() {
      var pdfUrl = "{{ $pdfUrl }}";
      var $flipContainer = $("#flipbook");
      var $statusEl = $("#status");
      
      $statusEl.text('Đang tải PDF với Turn.js...');
      
      pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
        var total = pdf.numPages;
        $statusEl.text('Đang render ' + total + ' trang với Turn.js...');
        
        // Tạo container cho Turn.js
        $flipContainer.html('<div id="magazine" class="turn-loading"></div>');
        
        // Khởi tạo Turn.js với kích thước tối đa
        $('#magazine').turn({
          width: 950,
          height: 700,
          autoCenter: true,
          display: 'double',
          acceleration: true,
          elevation: 50,
          gradients: true,
          when: {
            turning: function(event, page, view) {
              $statusEl.text('Trang ' + page + '/' + total);
            },
            turned: function(event, page, view) {
              $statusEl.text('Trang ' + page + '/' + total);
            }
          }
        });
        
        // Điều khiển nút cho Turn.js
        $("#btnPrev").off('click').on('click', function() {
          $('#magazine').turn('previous');
        });
        
        $("#btnNext").off('click').on('click', function() {
          $('#magazine').turn('next');
        });
        
        // Keyboard navigation cho Turn.js
        $(document).off('keydown.turnjs').on('keydown.turnjs', function(e) {
          if (e.keyCode === 37) { // Left arrow
            $('#magazine').turn('previous');
          } else if (e.keyCode === 39) { // Right arrow
            $('#magazine').turn('next');
          }
        });
        
        // Render PDF pages
        var renderPromises = [];
        for (var i = 1; i <= total; i++) {
          (function(pageNum) {
            var renderPromise = pdf.getPage(pageNum).then(function(page) {
              var viewport = page.getViewport({ scale: 1.5 });
              var canvas = document.createElement("canvas");
              var ctx = canvas.getContext("2d");
              canvas.width = viewport.width;
              canvas.height = viewport.height;
              
              return page.render({ canvasContext: ctx, viewport }).promise.then(function() {
                var pageDiv = $('<div class="page"></div>');
                var img = $('<img>').attr('src', canvas.toDataURL('image/jpeg', 0.9));
                pageDiv.append(img);
                $('#magazine').turn('addPage', pageDiv, pageNum);
                return pageNum;
              });
            });
            renderPromises.push(renderPromise);
          })(i);
        }
        
        Promise.all(renderPromises).then(function() {
          $statusEl.text('Hoàn tất (' + total + ' trang) - Turn.js flipbook');
          console.log('Turn.js flipbook initialized successfully');
        });
        
      }).catch(function(error) {
        console.error('Error loading PDF for Turn.js:', error);
        $statusEl.text('Lỗi tải PDF: ' + error.message);
        startSimpleFlipBook();
      });
    }

    function loadPDFAndCreateFlipbook() {
      var pdfUrl = "{{ $pdfUrl }}";
      var $flipContainer = $("#flipbook");
      var $statusEl = $("#status");
      
      // Sử dụng jQuery Deferred cho async operations
      var loadPDF = function() {
        var deferred = $.Deferred();
        
        $statusEl.text('Đang tải PDF...');
        
        pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
          var total = pdf.numPages;
          $statusEl.text('Đang render ' + total + ' trang...');
          
          // Khởi tạo StPageFlip với cấu hình tối ưu cho diện tích tối đa
          var flip = new St.PageFlip($flipContainer[0], {
            width: 550,
            height: 750,
            size: "stretch",
    showCover: true,
            drawShadow: true,
            flippingTime: 1000,
            usePortrait: false,
            autoSize: true,
            maxShadowOpacity: 0.5,
            mobileScrollSupport: true
          });
          
          var images = [];
          var renderPromises = [];
          
          // Render từng trang PDF thành hình ảnh với chất lượng cao
          for (var i = 1; i <= total; i++) {
            var renderPromise = pdf.getPage(i).then(function(page) {
              // Tăng scale để có chất lượng cao hơn
              var viewport = page.getViewport({ scale: 2.0 });
              var canvas = document.createElement("canvas");
              var ctx = canvas.getContext("2d");
              
              // Đặt kích thước canvas
    canvas.width = viewport.width;
    canvas.height = viewport.height;
              
              // Cải thiện chất lượng render
              ctx.imageSmoothingEnabled = true;
              ctx.imageSmoothingQuality = 'high';
              
              return page.render({ 
                canvasContext: ctx, 
                viewport: viewport 
              }).promise.then(function() {
                // Sử dụng JPEG với chất lượng cao để giảm kích thước file
                return canvas.toDataURL("image/jpeg", 0.95);
              });
            });
            renderPromises.push(renderPromise);
          }
          
          // Đợi tất cả trang được render
          Promise.all(renderPromises).then(function(imageDataUrls) {
            images = imageDataUrls;
            console.log('All pages rendered, total images:', images.length);
            
            // Tải hình ảnh vào flipbook - thử nhiều cách
            try {
              // Cách 1: Sử dụng loadFromImages
              flip.loadFromImages(images);
              console.log('StPageFlip loaded with loadFromImages successfully');
            } catch (error) {
              console.error('loadFromImages failed, trying loadFromHtml:', error);
              
              try {
                // Cách 2: Tạo HTML pages và sử dụng loadFromHtml
                $flipContainer.empty();
                
                for (var i = 0; i < images.length; i++) {
                  var pageDiv = $('<div class="page"></div>');
                  var img = $('<img>').attr('src', images[i]).css({
                    'width': '100%',
                    'height': '100%',
                    'object-fit': 'contain'
                  });
                  pageDiv.append(img);
                  $flipContainer.append(pageDiv);
                }
                
                // Sử dụng loadFromHtml thay vì khởi tạo lại
                flip.loadFromHtml($flipContainer.find('.page'));
                console.log('StPageFlip loaded with loadFromHtml successfully');
                
              } catch (error2) {
                console.error('loadFromHtml also failed, trying manual initialization:', error2);
                
                try {
                  // Cách 3: Khởi tạo lại StPageFlip
                  flip = new St.PageFlip($flipContainer[0], {
                    width: 550,
                    height: 750,
                    size: "stretch",
                    showCover: true,
                    drawShadow: true,
                    flippingTime: 1000,
                    usePortrait: false,
                    autoSize: true,
                    maxShadowOpacity: 0.5,
                    mobileScrollSupport: true
                  });
                  
                  // Thử loadFromHtml sau khi khởi tạo lại
                  flip.loadFromHtml($flipContainer.find('.page'));
                  console.log('StPageFlip reinitialized and loaded with loadFromHtml');
                  
                } catch (error3) {
                  console.error('All StPageFlip methods failed:', error3);
                  $statusEl.text('Lỗi tải hình ảnh vào flipbook - chuyển sang chế độ đơn giản');
                  startSimpleFlipBook();
                  return;
                }
              }
            }
            
            // Điều khiển nút với jQuery - sử dụng methods đúng theo tài liệu
            $("#btnPrev").off('click').on('click', function() {
              console.log('Previous button clicked');
              flip.flipPrev('bottom');
            });
            
            $("#btnNext").off('click').on('click', function() {
              console.log('Next button clicked');
              flip.flipNext('bottom');
            });
            
            // Keyboard navigation với jQuery
            $(document).off('keydown.flipbook').on('keydown.flipbook', function(e) {
              if (e.keyCode === 37) { // Left arrow
                console.log('Left arrow pressed');
                flip.flipPrev('bottom');
              } else if (e.keyCode === 39) { // Right arrow
                console.log('Right arrow pressed');
                flip.flipNext('bottom');
              }
            });
            
            // Event listeners
            flip.on('flip', function(e) {
              console.log('Page flipped to:', e.data);
              $statusEl.text('Trang ' + (e.data + 1) + '/' + total);
            });
            
            flip.on('changeOrientation', function(e) {
              console.log('Orientation changed:', e.data);
            });
            
            // Test flip functionality
            setTimeout(function() {
              console.log('Testing flip functionality...');
              if (typeof flip.flipNext === 'function') {
                console.log('flip.flipNext is available');
              } else {
                console.error('flip.flipNext is not available');
              }
            }, 1000);
            
            $statusEl.text('Hoàn tất (' + total + ' trang) - Có thể lật trang');
            console.log('StPageFlip initialized successfully with', total, 'pages');
            
            deferred.resolve();
          }).catch(function(error) {
            console.error('Error rendering pages:', error);
            $statusEl.text('Lỗi render trang: ' + error.message);
            deferred.reject(error);
          });
          
        }).catch(function(error) {
          console.error('Error loading PDF:', error);
          $statusEl.text('Lỗi tải PDF: ' + error.message);
          $flipContainer.html('<div class="loading">Lỗi tải PDF</div>');
          deferred.reject(error);
        });
        
        return deferred.promise();
      };
      
      // Gọi function loadPDF
      loadPDF();
    }

    // Fallback function khi StPageFlip không tải được
    function startSimpleFlipBook() {
      var pdfUrl = "{{ $pdfUrl }}";
      var $flipContainer = $("#flipbook");
      var $statusEl = $("#status");
      
      var currentPage = 1;
      var totalPages = 0;
      var pdfDoc = null;
      
      // Tạo container đơn giản
      $flipContainer.html(
        '<div style="display: flex; justify-content: center; align-items: center; height: 100%; background: #f5f5f5; border: 1px solid #ddd; border-radius: 10px;">' +
          '<div id="pdf-page-container" style="text-align: center;">' +
            '<canvas id="pdf-canvas" style="max-width: 100%; max-height: 100%; border: 1px solid #ccc; border-radius: 5px;"></canvas>' +
            '<div style="margin-top: 10px; font-size: 14px; color: #666;">' +
              'Trang <span id="current-page">1</span> / <span id="total-pages">0</span>' +
            '</div>' +
          '</div>' +
        '</div>'
      );
      
      var canvas = document.getElementById('pdf-canvas');
      var ctx = canvas.getContext('2d');
      
      // Điều khiển với jQuery
      $("#btnPrev").on('click', function() {
        if (currentPage > 1) {
          currentPage--;
          renderPage(currentPage);
        }
      });
      
      $("#btnNext").on('click', function() {
        if (currentPage < totalPages) {
          currentPage++;
          renderPage(currentPage);
        }
      });
      
      function renderPage(pageNum) {
        if (!pdfDoc) return;
        
        pdfDoc.getPage(pageNum).then(function(page) {
          // Tăng scale để có chất lượng cao hơn
          var viewport = page.getViewport({ scale: 2.0 });
          
          // Đặt kích thước canvas
          canvas.height = viewport.height;
          canvas.width = viewport.width;
          
          // Cải thiện chất lượng render
          ctx.imageSmoothingEnabled = true;
          ctx.imageSmoothingQuality = 'high';
          
          return page.render({
            canvasContext: ctx,
            viewport: viewport
          }).promise;
        }).then(function() {
          $("#current-page").text(pageNum);
          $statusEl.text('Trang ' + pageNum + '/' + totalPages + ' - Chế độ đơn giản');
        }).catch(function(e) {
          console.error('Error rendering page:', e);
          $statusEl.text('Lỗi hiển thị trang: ' + e.message);
        });
      }
      
      // Tải PDF
      $statusEl.text('Đang tải PDF...');
      
      pdfjsLib.getDocument(pdfUrl).promise.then(function(pdf) {
        pdfDoc = pdf;
        totalPages = pdf.numPages;
        
        $("#total-pages").text(totalPages);
        $statusEl.text('Đã tải PDF (' + totalPages + ' trang) - Chế độ đơn giản');
        
        // Hiển thị trang đầu tiên
        renderPage(1);
        
      }).catch(function(e) {
        console.error('Error loading PDF:', e);
        $statusEl.text('Lỗi tải PDF: ' + e.message);
      });
    }
</script>
</body>
</html>