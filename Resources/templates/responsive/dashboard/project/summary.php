<?php $this->layout('dashboard/project/layout') ?>

<?php $this->section('dashboard-content') ?>
<?php
    $validation = $this->project->getValidation();
 ?>
<div class="dashboard-content">
  <div class="inner-container">
    <h1><?= $this->text('dashboard-menu-activity-summary') ?></h1>
    <h5><?= $this->project->name ?></h5>

    <div class="panel section-content">
        <h3><?= $this->text('form-project-status-title') ?></h3>
        <div class="panel-body">

            <ol class="breadcrumb">
            <?php foreach ($this->statuses as $i => $s): ?>
                <?php if ($i == $this->project->status): ?>
                    <li class="active"><?= $s ?></li>
                <?php else: ?>
                    <li><?= $s ?></li>
                <?php endif ?>
            <?php endforeach ?>
            </ol>

            <?php if ($this->status_text): ?>
                <div class="spacer alert alert-danger"><?= $this->status_text ?></div>
            <?php endif ?>

            <?php if(!$this->project->isApproved()): ?>
                <?= $this->insert('project/widgets/validation', ['percent' => 0, 'validation' => $validation]) ?>
            <?php endif ?>

        </div>
    </div>

    <div class="panel section-content">
        <h3><?= $this->text('project-progress-title') ?></h3>

        <div class="panel-body">
            <ul class="data-list">
                <li>
                    <h5><?= $this->text('project-obtained') ?></h5>
                    <p><?= amount_format($this->project->invested) ?></p>
                </li>
                <li class="divider"></li>
                <li>
                    <h5><?= $this->text('project-menu-supporters') ?></h5>
                    <p><?= $this->project->num_investors ?></p>
                </li>
                <li class="divider"></li>
                <li>
                    <h5><?= $this->text('project-collaborations-number') ?></h5>
                    <p><?= $this->project->num_messengers ?></p>
                </li>
            </ul>

            <div class="chart-amount text-center"></div>
        </div>
    </div>

    <?php $url = $this->get_url() . '/widget/project/' . $this->project->id; ?>

    <div class="panel section-content">
        <h3><?= $this->text('project-spread-widget_title') ?></h3>
        <div class="panel-body widget-preview">
            <div class="right">
                <h5 onclick="$(this).next().focus();$(this).next().select()"><?= $this->text('project-spread-embed_code') ?></h5>
                <textarea class="form-control" onclick="this.focus();this.select()" readonly="readonly"><?= $this->text_widget($url) ?></textarea>
            </div>
            <div class="left">
                <iframe frameborder="0" height="492px" src="<?= $url ?>" width="300px" scrolling="no"></iframe>
            </div>
        </div>
    </div>

    <div class="panel section-content">
        <h3><?= $this->text('dashboard-project-delete') ?></h3>
        <a class="btn btn-danger" href="/project/delete/<?= $this->project->id ?>" onclick="return confirm('<?= $this->ee($this->text('dashboard-project-delete_alert'), 'js') ?>')"><i class="fa fa-trash"></i> <?= $this->text('regular-delete') ?></a>
    </div>

  </div>
</div>

<?php $this->replace() ?>

<?php $this->section('footer') ?>

<?php if($this->project->isApproved()): ?>
    <?= $this->insert('project/partials/chart_amount.php', ['project' => $this->project]) ?>
<?php endif ?>

<script type="text/javascript">
// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt

$(function(){
    var $c100 = $('.c100');
    var per = 0;
    var percent = <?= $validation->global ?>;
    $c100.removeClass('p0');
    (function animateCircle() {
        $c100.removeClass('p' + per);
        per++;
        $c100.addClass('p' + per);
        $c100.contents('span').text(per + '%');
        if(per < percent) {
            setTimeout(animateCircle, Math.ceil(per/5));
        }
    })();
})

// @license-end
</script>

<?php $this->append() ?>
