<?php if (!defined('APPLICATION')) exit();

/**
 * Garden.Modules
 */

/**
 * Renders the "New Review" button.
 */
class NewReviewModule extends Gdn_Module {

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function ToString() {
      $HasPermission = Gdn::Session()->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', 'any');
      if ($HasPermission)
         echo Anchor(T('New Review'), '/post/discussion?Type=Review', 'Button BigButton NewReview');
   }
}