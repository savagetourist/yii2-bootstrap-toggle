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
	 * Set these parameters to work with status changes by passing them to this method in the view
	 * @param int $toggleStatusDeleted set your STATUS_DELETED constant. You can not use 3 statuses, only active and inactive
	 * @param int $toggleStatusInactive set your STATUS_INACTIVE constant
	 * @param string $toggleActionStatusUrl set your processing request action url
	 * @param string $toggleModelName set item model name. Its need only for in output message to console
	 *
	 */

	public function setToggleParameters($toggleStatusDeleted = 2, $toggleStatusInactive = 1, $toggleActionStatusUrl = 'status', $toggleModelName = 'Item')
	{
		$view = Yii::$app->view;
		$view->registerJs('let toggleStatusDeleted = \'' . $toggleStatusDeleted . '\',
					toggleStatusInactive = \'' . $toggleStatusInactive . '\',
					toggleActionStatusUrl = \'' . $toggleActionStatusUrl . '\',
					toggleModelName = \'' . $toggleModelName . '\';');

		$changeStatusUsingAction = <<<JS
		/** EVENT: TRASH */
		$('div.trash').click(function (){
			var toggleLabel = $(this).prev('label'),
				toggle = toggleLabel.find('input'),
				id = toggle.attr('data-item'),
				dataId = toggle.attr('id'),
				status = toggle.attr('data-status');
				data = {
					'id': id,
					'dataId': dataId,
					'status': status,
					'trash': 1
				}
				
			if (toggle.attr('disabled')) {
				console.log(toggleModelName + ' has already been deleted.');
				return false;
			}
			else{
				if (confirm('Are you sure you want to delete this item?')){
					$.ajax({
					url: toggleActionStatusUrl,
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
							console.log(toggleModelName + ' ' + data.id + ' has been deleted');
						}
						else {
							console.log(toggleModelName + ' has already been deleted.');
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
			var toggle = $(this).find('input');
			// console.log(toggle.next('.toggle-group').find('.toggle-off').html());
			if (toggle.attr('disabled') && toggle.attr('data-status') == toggleStatusDeleted){
				toggle.next('.toggle-group').find('.toggle-off').html('<i class="fa fa-pause"></i>');
				$(this).removeClass('deleted');
				toggle.attr('data-status', toggleStatusInactive);
				toggle.bootstrapToggle('enable');
				toggle.bootstrapToggle('off');
			}
		})
		
		/** EVENT: CHANGE STATUS (TOGGLE) */
		$('input[id*="toggle-"]').change(function(){
			$(this).bootstrapToggle('disable');
			if ($(this).attr('data-status') == toggleStatusDeleted){
				$(this).bootstrapToggle('destroy');
				$(this).bootstrapToggle('off');
				$(this).prev('.toggle').removeClass('deleted');
			}
			var dataItem = $(this).attr('data-item'),
				dataId = $(this).attr('id'),
				data = {
				'id': dataItem,
				'dataId': dataId
			}
			$.ajax({
			url: toggleActionStatusUrl,
			type: 'post',
			data: data
			})
			.done(function (data){
				if (data.success){
					$('#' + data.dataId).attr('data-status', data.status);
					$('#' + data.dataId).bootstrapToggle('enable');
					console.log(toggleModelName + ' ' + data.id + ' status has been changed');
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
