<?php if (!defined('APPLICATION')) exit(); ?>
<div class="P">
   <?php echo T('You can either ask a question, start a discussion, or write a review', 'You can either ask a question, start a discussion, or write a review. Choose what you want to do below.'); ?>
</div>
<style>.NoScript { display: none; }</style>
<noscript>
   <style>.NoScript { display: block; } .YesScript { display: none; }</style>
</noscript>
<div class="P NoScript">
   <?php echo $Form->RadioList('Type', array('Review' => T('New Review'), 'Discussion' => T('Start a New Discussion'))); ?>
</div>
<div class="YesScript">
   <div class="Tabs">
      <ul>
         <li class="<?php echo $Form->GetValue('Type') == 'Review' ? 'Active' : '' ?>"><a id="NewReview_Discussion" class="NewReviewButton TabLink" rel="Revivew" href="#"><?php echo T('New Review'); ?></a></li>  
      </ul>
   </div>
</div>
