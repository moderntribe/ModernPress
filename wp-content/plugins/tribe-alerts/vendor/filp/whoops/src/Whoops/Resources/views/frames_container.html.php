<div class="frames-container <?php 
namespace Tribe\Alert_Scoped;

echo $active_frames_tab == 'application' ? 'frames-container-application' : '';
?>">
  <?php 
$tpl->render($frame_list);
?>
</div><?php 
