<?php
/**
 * @link https://github.com/loveorigami/yii2-bootstrap-toggle
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace st\widgets;

use yii\helpers\Html;
use yii\widgets\InputWidget;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use Yii;

/**
 * Toogle renders a checkbox type toggle switch control. For example:
 *
 * ```
 * <?= \lo\widgets\Toggle::widget([
 *      'name' => 'Test',
 *      'options' => [
 *          'data-size' => 'large',
 *      ]
 *  ]);?>
 * ```
 *
 * @author Andrey Lukyanov <loveorigami@mail.ru>
 * @editor Andrey Atyashov <atyashov1994@yandex.ru>
 * @link http://www.loveorigami.info
 * @package st\widgets\Toogle
 */
class Toggle extends InputWidget
{
	/**
	 * @var bool specifies whether the checkbox should be checked or unchecked, when not used with a model. If [[items]],
	 * [[$checked]] will specify the value to select.
	 */
	public $checked = false;

	/**
	 * @var array the options for the Bootstrap Toogle plugin.
	 * Please refer to the Bootstrap Toogle plugin Web page for possible options.
	 * @see http://www.bootstraptoggle.com
	 */
	public $options = [];

	/**
	 * @var array the default options for the widget.
	 */
	protected $woptions = [
		'data-toggle' => 'toggle',
		'data-onstyle' => 'success',
		'data-offstyle' => 'danger',
		'label' => false,
	];

	/**
	 * @var array the event handlers for the underlying Bootstrap Toggle input JS plugin.
	 * Please refer to the [Bootstrap Toggle](http://www.bootstraptoggle.com/#events) plugin
	 * Web page for possible events.
	 */
	public $clientEvents = [];

	/**
	 * @var string the DOM element selector
	 */
	protected $selector;

	/**
	 * @var bool whether to display the label inline or not. Defaults to true.
	 */
	public $inlineLabel = true;

	/**
	 * Call function setToggleParameters on page where use Toglles with model update
	 */

	public static function setToggleParameters()
	{
		$view = Yii::$app->view;

		$changeStatusUsingAction = <<<JS
		/** EVENT: TRASH */
		$('div.trash').click(function (){
			var toggleLabel = $(this).prev('label'),
				toggle = toggleLabel.find('input'),
				id = toggle.attr('data-item'),
				itemName = toggle.attr('data-name'),
				toggleUrl = toggle.attr('data-url'),
				statusDeleted = toggle.attr('data-deleted'),
				statusBlocked = toggle.attr('data-blocked'),
				dataId = toggle.attr('id'),
				status = toggle.attr('data-status');
				data = {
					'id': id,
					'dataId': dataId,
					'status': status,
					'trash': 1
				}
				
			if (toggle.attr('disabled')) {
				console.log(itemName + ' has already been deleted.');
				return false;
			}
			else{
				if (confirm('Are you sure you want to delete this item?')){
					$.ajax({
					url: toggleUrl,
					type: 'post',
					data: data
					})
					.done(function(data){
						if (data.success){
							$('#' + data.dataId).attr('data-status', data.status);
							$('#' + data.dataId).attr('disabled', 'disabled');
							$('#' + data.dataId).parent('.toggle').addClass('btn-danger off deleted');
							$('#' + data.dataId).next('.toggle-group').find('.toggle-off').html('<i class="fa fa-trash"></i>');
							$('#' + data.dataId).parent('.toggle').removeClass('btn-success');
							console.log(itemName + ' ' + data.id + ' has been deleted');
						}
						else {
							console.log(itemName + ' has already been deleted.');
						}
					})
					.fail(function(){
						console.log('An error occurred while sending data!');
					})
				}
			}
		});
		
		/** EVENT: RECOVERY */
		$('.toggle').click(function (){
			var toggle = $(this).find('input'),
				itemName = toggle.attr('data-name'),
				toggleUrl = toggle.attr('data-url'),
				statusDeleted = toggle.attr('data-deleted'),
				statusBlocked = toggle.attr('data-blocked');
			if (toggle.attr('disabled') && toggle.attr('data-status') == statusDeleted){
				toggle.next('.toggle-group').find('.toggle-off').html('<i class="fa fa-pause"></i>');
				$(this).removeClass('deleted');
				toggle.attr('data-status', statusBlocked);
				toggle.bootstrapToggle('enable');
				toggle.bootstrapToggle('off');
			}
		})
		
		/** EVENT: CHANGE STATUS (TOGGLE) */
		$('input[id*="toggle-"]').change(function(){
				var dataItem = $(this).attr('data-item'),
				dataId = $(this).attr('id'),
				data = {
				'id': dataItem,
				'dataId': dataId
				},
				itemName = $(this).attr('data-name'),
				toggleUrl = $(this).attr('data-url'),
				statusDeleted = $(this).attr('data-deleted');
			$(this).bootstrapToggle('disable');
			if ($(this).attr('data-status') == statusDeleted){
				$(this).bootstrapToggle('destroy');
				$(this).bootstrapToggle('off');
				$(this).prev('.toggle').removeClass('deleted');
			}

			$.ajax({
			url: toggleUrl,
			type: 'post',
			data: data
			})
			.done(function (data){
				if (data.success){
					$('#' + data.dataId).attr('data-status', data.status);
					$('#' + data.dataId).bootstrapToggle('enable');
					console.log(itemName + ' ' + data.id + ' status has been changed');
				}else{
					console.log('Error: ' + JSON.stringify(data.error));
				}
			})
			.fail(function (data) {
				$('#' + data.dataId).bootstrapToggle('enable');
				$('#' + data.dataId).bootstrapToggle('toggle');
				console.log('An error occurred while sending data!');
			})
			return false;
		});
JS;
		$view->registerJs($changeStatusUsingAction);
		$view->registerCss('.toggle.deleted{filter: grayscale(1);}');
	}

	/**
	 * Registers Bootstrap Switch plugin and related events
	 */
	public function registerClientScript()
	{
		$view = $this->view;
		ToggleAsset::register($view);
		//$this->clientOptions['animate'] = ArrayHelper::getValue($this->clientOptions, 'animate', true);
		$options = Json::encode($this->options);
		$js[] = "jQuery('$this->selector').bootstrapToggle($options);";
		if (!empty($this->clientEvents)) {
			foreach ($this->clientEvents as $event => $handler) {
				$js[] = "jQuery('$this->selector').on('$event', $handler);";
			}
		}
		$view->registerJs(implode("\n", $js));

	}

	public function run()
	{

		$this->options = ArrayHelper::merge($this->woptions, $this->options);

		if ($this->hasModel()) {
			$input = Html::activeCheckbox($this->model, $this->attribute, $this->options);
		} else {
			$input = Html::checkbox($this->name, $this->checked, $this->options);
		}

		echo $this->inlineLabel ? $input : Html::tag('div', $input);
		$this->selector = "#{$this->options['id']}";

		$this->registerClientScript();

	}
}
