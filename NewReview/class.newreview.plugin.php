<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['NewReview'] = array(
   'Name' => 'NewReview',
   'Description' => "Users may designate a discussion as a New Review and post a product/service review.",
   'Version' => '1.0.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => 'Rangerine',
   'AuthorEmail' => 'rangerine@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/rangerine'
);
/**
 * Adds New Review format to Vanilla.
 *
 * You can set Plugins.NewReview.UseBigButtons = TRUE in config to separate 'New Discussion'
 * and 'New Review' into "separate" forms each with own big button in Panel.
 * Original plugin was developed by Todd Burry (todd@vanillaforums.com) and edited by me.
 */
class NewReviewPlugin extends Gdn_Plugin {
   /// PROPERTIES ///

   /// METHODS ///
   public function Setup() {
      $this->Structure();
   }

   public function Structure() {
      Gdn::Structure()
         ->Table('Discussion');

      $NewReviewExists = Gdn::Structure()->ColumnExists('NewReview'); //new table element for reviews
      $DateReviewAcceptedExists = Gdn::Structure()->ColumnExists('DateReviewAccepted'); //new table element for accepted review date

      Gdn::Structure()
         ->Column('NewReview', array('Accepted', 'Rejected'), NULL, 'index') //review either has accepted or rejected status
         ->Column('DateReviewAccepted', 'datetime', TRUE) // The date review was accepted
		 ->Column('ReviewerUserID', 'int', TRUE) //sets column for the reviewers user ID
		 ->Column('IsReview', 'int', '0')
         ->Set(); //sets the above three rows in sql structure

      Gdn::Structure()
         ->Table('User') //calls the user table from sql
         ->Column('CountAcceptedReviews', 'int', '0') //counts accepted reviews
         ->Set(); //sets the above two rows in sql structure
   }
   /// EVENTS ///
   public function Base_BeforeCommentDisplay_Handler($Sender, $Args) {
      $NewReview = GetValueR('Comment.NewReview', $Args);

      if ($NewReview && isset($Args['CssClass'])) {
         $Args['CssClass'] = ConcatSep(' ', $Args['CssClass'], "NewReview-Item-$NewReview");
      }
   }

   public function Base_DiscussionTypes_Handler($Sender, $Args) {
      $Args['Types']['Review'] = array(
            'Singular' => 'Review',
            'Plural' => 'Reviews',
            'AddUrl' => '/post/review',
            'AddText' => 'New Review'
            );
   }
   //looks like event handler, if comments for question it goes to below code, if for discussion it goes to standard discussion comment
   public function DiscussionController_CommentOptions_Handler($Sender, $Args) {
      $Comment = $Args['Comment'];
      if (!$Comment)
         return;
      $Discussion = Gdn::Controller()->Data('Discussion');

      if (GetValue('Type', $Discussion) != 'Review')
         return;

      if (!Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         return;

      $Args['CommentOptions']['NewReview'] = array('Label' => T('NewReview').'...', 'Url' => '/discussion/newreviewoptions?commentid='.$Comment->CommentID, 'Class' => 'Popup');
   }

   public function Base_DiscussionOptions_Handler($Sender, $Args) {
      $Discussion = $Args['Discussion'];
      if (!Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         return;

      if (isset($Args['DiscussionOptions'])) {
         $Args['DiscussionOptions']['NewReview'] = array('Label' => T('NewReview').'...', 'Url' => '/discussion/newreviewoptions?discussionid='.$Discussion->DiscussionID, 'Class' => 'Popup');
      } elseif (isset($Sender->Options)) {
         $Sender->Options .= '<li>'.Anchor(T('NewReview').'...', '/discussion/newreviewoptions?discussionid='.$Discussion->DiscussionID, 'Popup NewReviewOptions') . '</li>';
      }
   }
   /**
    *
    * @param DiscussionController $Sender
    * @param array $Args
    */
   public function DiscussionController_NewReview_Create($Sender, $Args = array()) {
      $Comment = Gdn::SQL()->GetWhere('Comment', array('CommentID' => $Sender->Request->Get('commentid')))->FirstRow(DATASET_TYPE_ARRAY);
      if (!$Comment)
         throw NotFoundException('Comment');

      $Discussion = Gdn::SQL()->GetWhere('Discussion', array('DiscussionID' => $Comment['DiscussionID']))->FirstRow(DATASET_TYPE_ARRAY);

      // Check for permission.
      if (!(Gdn::Session()->UserID == GetValue('InsertUserID', $Discussion) || Gdn::Session()->CheckPermission('Garden.Moderation.Manage'))) {
         throw PermissionException('Garden.Moderation.Manage');
      }
      if (!Gdn::Session()->ValidateTransientKey($Sender->Request->Get('tkey')))
         throw PermissionException();

      if (isset($NewReview)) {
         $DiscussionSet = array('NewReview' => $NewReview);
         $CommentSet = array('NewReview' => $NewReview);
         // Update the comment.
         Gdn::SQL()->Put('Comment', $CommentSet, array('CommentID' => $Comment['CommentID']));

      }
      Redirect("/discussion/comment/{$Comment['CommentID']}#Comment_{$Comment['CommentID']}");
   }

   public function DiscussionController_NewReviewOptions_Create($Sender, $DiscussionID = '', $CommentID = '') {
      if ($DiscussionID)
         $this->_DiscussionOptions($Sender, $DiscussionID);
      elseif ($CommentID)
         $this->_CommentOptions($Sender, $CommentID);

   }

   protected function _DiscussionOptions($Sender, $DiscussionID) {
      $Sender->Form = new Gdn_Form();

      $Discussion = $Sender->DiscussionModel->GetID($DiscussionID);

      if (!$Discussion)
         throw NotFoundException('Discussion');

      $Sender->permission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $Discussion));

      // Both '' and 'Discussion' denote a discussion type of discussion.
      if (!GetValue('Type', $Discussion))
         SetValue('Type', $Discussion, 'Discussion');

      if ($Sender->Form->IsPostBack()) {
         $Sender->DiscussionModel->SetField($DiscussionID, 'Type', $Sender->Form->GetFormValue('Type'));

         $Sender->Form->SetValidationResults($Sender->DiscussionModel->ValidationResults());

         Gdn::Controller()->JsonTarget('', '', 'Refresh');
      } else {
         $Sender->Form->SetData($Discussion);
      }

      $Sender->SetData('Discussion', $Discussion);
      $Sender->SetData('_Types', array('Review' => '@'.T('Review Type', 'Review'), 'Discussion' => '@'.T('Discussion Type', 'Discussion')));
      $Sender->SetData('Title', T('Review Options'));
      $Sender->Render('DiscussionOptions', '', 'plugins/NewReview');
   }
   /**
    * Add 'New Review' button if using BigButtons.
    */
   public function CategoriesController_Render_Before($Sender) {
      if (C('Plugins.NewReview.UseBigButtons')) {
         $ReviewModule = new NewReviewModule($Sender, 'plugins/NewReview');
         $Sender->AddModule($NewReviewModule);
      }
   }
   /**
    * Add 'New Review' button if using BigButtons.
    */
   public function DiscussionController_Render_Before($Sender) {
      if (C('Plugins.NewReview.UseBigButtons')) {
         $ReviewModule = new NewReviewModule($Sender, 'plugins/NewReview');
         $Sender->AddModule($ReviewModule);
      }

      if ($Sender->Data('Discussion.Type') == 'Review') {
         $Sender->SetData('_CommentsHeader', T('Comments'));
      }
   }
   /**
    * Add the review form to vanilla's post page.
    */
   public function PostController_AfterForms_Handler($Sender) {
      $Forms = $Sender->Data('Forms');
      $Forms[] = array('Name' => 'Review', 'Label' => Sprite('SpReview').T('New Review'), 'Url' => 'post/review');
		$Sender->SetData('Forms', $Forms);
   }

   /**
    * Create the new review method on post controller.
    */
   public function PostController_Review_Create($Sender, $CategoryUrlCode = '') {
      // Create & call PostController->Discussion()
      $Sender->View = PATH_PLUGINS.'/NewReview/views/post.php';
      $Sender->SetData('Type', 'Review');
      $Sender->Discussion($CategoryUrlCode);
   }

   /**
    * Override the PostController->Discussion() method before render to use our view instead.
    */
   public function PostController_BeforeDiscussionRender_Handler($Sender) {
      // Override if we are looking at the review url.
      if ($Sender->RequestMethod == 'review') {
         $Sender->Form->AddHidden('Type', 'Review');
		 $Sender->Form->AddHidden('IsReview');
         $Sender->Title(T('New Review'));
         $Sender->SetData('Breadcrumbs', array(array('Name' => $Sender->Data('Title'), 'Url' => '/post/review')));
      }
   }
   /**
    * Add 'New Review Form' location to Messages.
    */
   public function MessageController_AfterGetLocationData_Handler($Sender, $Args) {
      $Args['ControllerData']['Vanilla/Post/Review'] = T('New Review Form');
   }
}
