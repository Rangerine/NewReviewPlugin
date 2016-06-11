<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$CancelUrl = '/discussions';
if (C('Vanilla.Categories.Use') && is_object($this->Category))
   $CancelUrl = '/categories/'.urlencode($this->Category->UrlCode);
   
    //options for dropdowns
    // In HTML you have input type text and textareas. Vanilla only has TextBoxes.
    // In order to make them multiline, you need an options array like that.
    $textBoxOptions = $this->Data('TextBoxOptions');
    // Some Example Data for a RadioList.
    $radioListData = $this->Data('RadioListData');
    // You can define which radio button should be preselected.
    $radioListOptions = $this->Data('RadioListOptions');

    // For a dropdown, you can use the same data as for the RadioList.
    $dropDownData = $radioListData;
    // But for setting a standard value, you have to user "value".
    $dropDownOptions = $this->Data('DropDownOptions');   
   
   //end options for dropdowns
?>
<div id="DiscussionForm" class="FormTitleWrapper DiscussionForm">
   <?php
		if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
			echo Wrap($this->Data('Title'), 'h1', array('class' => 'H'));

      echo '<div class="FormWrapper">';
      echo $this->Form->Open();
      echo $this->Form->Errors();
      $this->FireEvent('BeforeFormInputs');

      if ($this->ShowCategorySelector === TRUE) {
			echo '<div class="P">';
				echo '<div class="Category">';
				echo $this->Form->Label('Category', 'CategoryID'), ' ';
				echo $this->Form->CategoryDropDown('CategoryID', array('Value' => GetValue('CategoryID', $this->Category), 'PermFilter' => array('AllowedDiscussionTypes' => 'Review')));
				echo '</div>';
			echo '</div>';
      }

          echo '<div class="P">';
			echo $this->Form->Label('Review', 'Name');
			echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
		echo '</div>';
		//Form Input Fields Start
				//Product Name
	  echo '<div class="P">';
			echo $this->Form->Label('Product Name', 'TestField0');
			echo Wrap($this->Form->TextBox('TestField0', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
		echo '</div>';
		//Date Purchased
	  echo '<div class="P">';
			echo $this->Form->Label('Date Purchased', 'TestField1');
			echo Wrap($this->Form->TextBox('TestField1', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
		echo '</div>';		
		//Location Purchased, amazon, newegg, etc.	
	  echo '<div class="P">';
			echo $this->Form->Label('Location Purchased', 'TestField1');
			echo Wrap($this->Form->TextBox('TestField1', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
		echo '</div>';	
		//Recommended?
	  echo '<div class="P">';
			echo $this->Form->Label('Recommended', 'RadioListExample');
			echo Wrap($this->Form->DropDown('RadioListExample', $dropDownData, $dropDownOptions));
		echo '</div>';		
		//Form Input Fields End
		
		
		$this->FireEvent('BeforeBodyInput');
		echo '<div class="P">';
         echo $this->Form->BodyBox('Body', array('Table' => 'Discussion', 'FileUpload' => true));
		echo '</div>';

		$this->FireEvent('AfterDiscussionFormOptions');

      echo '<div class="Buttons">';
      $this->FireEvent('BeforeFormButtons');
      echo $this->Form->Button((property_exists($this, 'Discussion')) ? 'Save' : 'Post Review', array('class' => 'Button Primary DiscussionButton'));
      if (!property_exists($this, 'Discussion') || !is_object($this->Discussion) || (property_exists($this, 'Draft') && is_object($this->Draft))) {
         echo ' '.$this->Form->Button('Save Draft', array('class' => 'Button Warning DraftButton'));
      }
      echo ' '.$this->Form->Button('Preview', array('class' => 'Button Warning PreviewButton'));
      echo ' '.anchor(t('Edit'), '#', 'Button WriteButton Hidden')."\n";
      $this->FireEvent('AfterFormButtons');
      echo ' '.Anchor(T('Cancel'), $CancelUrl, 'Button Cancel');
      echo '</div>';
      echo $this->Form->Close();
      echo '</div>';
   ?>
</div>
