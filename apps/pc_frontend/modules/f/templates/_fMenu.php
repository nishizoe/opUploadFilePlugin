<style>
<!--
#file-uploadmodal .modal-message {
  text-align: center;
  font-size: 120%;
  padding: 2em 0;
}

#file-uploadmodal .modal-footer {
  text-align: center;
}

#file-uploadmodal .btn {
  float: none;
  width: 30%;
}

.file-item {
  width: 270px;
  overflow: hidden;
}

.file-item a {
  margin: 1px;
  overflow: hidden;
}
-->
</style>

<ul class="nav pull-right" id="file-menu" style="display: none">
<li class="dropdown">
  <a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="icon-folder-open icon-white"></i><b class="caret"></b></a>
  <ul class="dropdown-menu" id="file-menuitems">
    <li><a href="#file-uploadmodal" data-toggle="modal">アップロード</a></li>
    <li class="divider"></li>
  </ul>
</li>
</ul>

<script id="file-menuitem-template" type="text/x-jquery-tmpl">
{{each data}}
<li class="file-item">
  <a href="<?php echo $sf_request->getRelativeUrlRoot(); ?>/f/show/${id}${ext}" data-file-path="${name}" data-file-name="${original_filename}">
    <button data-action="delete"><i class="icon-trash"></i></button>
    ${original_filename}
  </a>
</li>
{{/each}}
</script>

<script>
function init()
{
    $('#file-uploadmodal-body1').show();
    $('#file-uploadmodal-body2').hide();
    $('#file-uploadmodal-message').text('ファイルをアップロードします');
    $('#file-uploadsubmit-loading').hide();
    $('#file-uploadmodal-upfile').val('');
    $('#file-uploadsubmit').removeAttr('disabled');
    $('#file-uploadsubmit').show();
}

function getUploadedFileList ()
{
  $.getJSON(
    openpne.apiBase + 'f/list',
    {
      apiKey: openpne.apiKey,
      path: '/m<?php echo $member->getId() ?>'
    },
    function(json) {
      if ('success' !== json.status)
      {
        alert('ファイル一覧の取得時にエラーが発生しました。');
        return false;
      }
      $('#file-menuitems .file-item').remove();
      var menuitem = $('#file-menuitem-template').tmpl(json);
      $('button', menuitem).click(function(event){
        if (!confirm($(this.parentNode).data('fileName') + ' を削除しますか？'))
        {
          return;
        }
        $.post(
          openpne.apiBase + 'f/delete',
          {
            apiKey: openpne.apiKey,
            path: $(this.parentNode).data('filePath')
          },
          function(json) {
            if ('success' === json.status)
            {
              alert('削除しました');
            }
            else
            {
              alert('削除に失敗しました');
            }
          },
          'JSON'
        );
      });

      $('a', menuitem).click(function(event){
        if (-1 !== $.inArray(event.target.tagName, ['BUTTON', 'I']))
        {
          event.preventDefault();
        }
      });
      $('#file-menuitems').append(menuitem);
    }
  );
}

$('#file-menu .dropdown-toggle').click(function(){
  if ('' == $('#file-uploadmodal-isuploading').val())
  {
    init();
  }
  getUploadedFileList();
});

$(function(){
  $('#file-menu').detach().insertAfter('.nav.pull-right:first').show();
  getUploadedFileList();
});
</script>

<div id="file-uploadmodal" class="modal" tabindex="-1" style="display: none">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h3 id="file-uploadmodal-label">アップロード</h3>
  </div>
  <div class="modal-body" id="file-uploadmodal-body1">
    <p class="modal-message" id="file-uploadmodal-message">ファイルをアップロードします</p>
    ファイル: <input name="upfile" type="file" id="file-uploadmodal-upfile">
    <?php echo op_image_tag('ajax-loader.gif', array('class' => 'hide', 'id' => 'file-uploadsubmit-loading')) ?>
    <input type="hidden" name="isuploading" id="file-uploadmodal-isuploading" value="" />
  </div>
  <div class="modal-body hide" id="file-uploadmodal-body2">
    <p class="modal-message" id="file-uploadmodal-message">アップロードしました</p>
    <input type="input" id="file-upload-url" size="80" />
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" id="file-uploadsubmit">
      <span id="file-uploadsubmit-text">アップロード</span>
    </button>
  </div>
</div>

<script>
function getByte(str)
{
  if (str.length == 0)
  {
    return 0;
  }
  var cnt = 0;
  var val = '';
  var strlen = str.length;
  for (var i = 0; i < strlen; i++)
  {
    val = str.charAt(i);
    val = escape(val);
    var vallen = val.length;
    if (vallen  < 4)
    {
      cnt = cnt + 1;
    }
    else
    {
      cnt = cnt + 2;
    }
  }
  return cnt;
}

$('#file-uploadsubmit').click(function(){
  // inputcheck
  var uploadFileName = $('#file-uploadmodal-upfile').val();
  if ('' == uploadFileName)
  {
    alert('アップロードするファイルを選択してください。');
    return false;
  }
  if (50 < getByte(uploadFileName))
  {
    alert('アップロードするファイルの名前の長さは16文字以下にしてください。');
    return false;
  }

  var path = $('#file-uploadmodal-upfile').val();
  var tmpFileName = path.replace(/\\/g,'/').replace( /.*\//g, '-' );

  $('#file-uploadmodal-message').text('アップロード中...');
  $('#file-uploadmodal-isuploading').val('1');
  $('#file-uploadsubmit').attr('disabled', 'disabled');
  $('#file-uploadsubmit-loading').show();

  $('#file-uploadmodal .modal-body').upload(
    openpne.apiBase + 'f/upload',
    {
      apiKey: openpne.apiKey,
      forceHtml: 1,
      changedname: tmpFileName
    },
    function(json) {
      json = JSON.parse(json);
      $('#file-uploadsubmit').hide();
      if ('success' !== json.status)
      {
        alert('アップロード時にエラーが発生しました。');
        $('#file-uploadmodal-isuploading').val('');
        return false;
      }
      var orgFilename = json['file']['original_filename'];
      var separates = orgFilename.split('.');
      var len = separates.length;
      var filename = json['file']['id'];
      var ext = '';
      if (1 < len)
      {
        ext = '.' + separates[len - 1];
      }
      var url = '<?php echo $sf_request->getUriPrefix().$sf_request->getRelativeUrlRoot() ?>/f/show/' + filename + ext;
      $('#file-upload-url').val(url);
      $('#file-uploadmodal-body1').hide();
      $('#file-uploadmodal-body2').show();
      $('#file-uploadmodal-isuploading').val('');
    },
    'text'
  );
});
$(document).ready(function(){
  $('#file-upload-url').click(function(){
    $(this).select();
  });
}); 
</script>
