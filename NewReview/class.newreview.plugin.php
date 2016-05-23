<?php // if (!defined('APPLICATION')) exit(); <- this one is not used any more. 
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
// Define the plugin:
$PluginInfo['NewReview'] = array(
   'Name' => 'NewReview',
   'Description' => "Users may designate a discussion as a New Review and post a product/service review.",
   'Version' => '1.0.0',
   'RequiredApplications' => array('Vanilla' => '2.2'), // I always use the current version to motivate users of my plugins to update
    'MobileFriendly' => true, // If you learn to write plugins, why not use the new coding standard, which uses 4 spaces for indenting, by the way ;)
   'RegisterPermissions' => array('NewReview.Add'), // Do you want to allow really all of your users to write reviews? If not, you should add a custom permission so that you can decide who is allowed to write reviews.
   'Author' => 'Rangerine',
   // 'AuthorEmail' => 'rangerine@vanillaforums.com', <- I doubt that. You do not need to provide a mail address
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
    // This function is called any time the plugin is enabled.
   public function Setup() {
      $this->Structure();
   }

    // When you make an update of your forum you are encouraged to run /utility/structure.
    // This will loop through all enabled plugins as well and call their structure() method.
    // That should just be an explanation why you should always make your db changes in a separate
    // method called "structure()" like it has been done here.
    public function structure() { // new coding standard (I'll change some things from time to time because of that)
      Gdn::Structure()
         ->Table('Discussion');
	// That's why I don't like this approach: I do not have a clue what these things are good for
      $NewReviewExists = Gdn::Structure()->ColumnExists('NewReview'); //new table element for reviews
      $DateReviewAcceptedExists = Gdn::Structure()->ColumnExists('DateReviewAccepted'); //new table element for accepted review date

      Gdn::Structure()
      // Do you want to moderate reviews? I would try to use a Vanilla in-built feature but I'm not sure how easy this would be:
      // You can define role permissions so that new discussions cannot be added but need to be moderated. Since you do not want
      // all discussions to be moderated, you would have to change the role permission "on the fly". I guess you would have to change
      // that permission before such a review is saved, and you can reset it later. I would have to do some research if you are interested
      // in this. At first I would try starting to go without restrictions/moderation.
         ->Column('NewReview', array('Accepted', 'Rejected'), NULL, 'index') //review either has accepted or rejected status
         ->Column('DateReviewAccepted', 'datetime', TRUE) // The date review was accepted
		 ->Column('ReviewerUserID', 'int', TRUE) //sets column for the reviewers user ID <- shouldn't that be identical with the creator of the discussion? That would be InsertUserID and is already in the table
		 ->Column('IsReview', 'int', '0')
         ->Set(); //sets the above three rows in sql structure

      Gdn::Structure()
         ->Table('User') //calls the user table from sql
         ->Column('CountAcceptedReviews', 'int', '0') //counts accepted reviews
         ->Set(); //sets the above two rows in sql structure <- one row only ;-)
   }
   /// EVENTS ///
   public function Base_BeforeCommentDisplay_Handler($Sender, $Args) {
      $NewReview = GetValueR('Comment.NewReview', $Args);

      if ($NewReview && isset($Args['CssClass'])) {
         $Args['CssClass'] = ConcatSep(' ', $Args['CssClass'], "NewReview-Item-$NewReview");
      }
   }

    // There is some magic here. The "New Discussion" button is rendered by a module: a small
    // piece of code that does a small part of the html output. This module loops through all available
    // DiscussionTypes and creates either a simple button if there is only "Discussion" in that array
    // or a button with a drop down if there are several DiscussionTypes
   public function Base_DiscussionTypes_Handler($Sender, $Args) {
      $Args['Types']['Review'] = array(
            'Singular' => 'Review',
            'Plural' => 'Reviews',
            'AddUrl' => '/post/review',
            'AddText' => 'New Review'
            );
   }
   //looks like event handler, if comments for question it goes to below code, if for discussion it goes to standard discussion comment
   // Event handlers here consist of three part: classname_eventname_"action"(object $sender = instance(?) of the class, array $args = useful parameters, also accessible as $sender->EventArguments[])
   // If you read DiscussionController_... you know that this event will only be fired if you are looking at yourforum.com/discussion/...
   // The eventname is CommentOptions and is fired so that you can add elements to the comment option dropdown list
   // There is either handler or create: "handler" simply handles existing events, "create" allows you to create new "endpoints"
   // Try the following: public function discussionController_hello_create($sender, $args) { decho($args); } and call yourforum.com/hello/world
   public function DiscussionController_CommentOptions_Handler($Sender, $Args) {
      // I don't think this is useful at all ( the downside of using old code)
      $Comment = $Args['Comment'];
      if (!$Comment)
         return;
         // Not sure about how useful this is. I would think it is identical to $sender
      $Discussion = Gdn::Controller()->Data('Discussion');

      // Make sure this comment is a comment of a discussion of type review, because you wouldn't show your options to a normal discussion
      if (GetValue('Type', $Discussion) != 'Review')
         return;

	// Check if current user is allowed to edit(!) discussions in current category. You surely do not need that.
      if (!Gdn::Session()->CheckPermission('Vanilla.Discussions.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID))
         return;
	// Why do you like to add a comment option "New Review"? Do you want your users to be able to review comments?
      $Args['CommentOptions']['NewReview'] = array('Label' => T('NewReview').'...', 'Url' => '/post/newreviewoptions?commentid='.$Comment->CommentID, 'Class' => 'Popup'); // I would use post controller
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
