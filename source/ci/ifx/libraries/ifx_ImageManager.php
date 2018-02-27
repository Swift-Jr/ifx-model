<?php

    class ifx_ImageManager extends ifx_Library {
        static $uploadroot = null;
        static $imageroot = null;
        static $model = 'mImage';
        static $group = null;

        var $after = false;

        function do_upload($customconfig = array()) {
            //Will only be one file at a time being uploaded.
            $this->ci->load->library('upload');
            $Upload = &$this->ci->upload;

            $config['upload_path'] = static::$uploadroot;
            $config['allowed_types'] = 'gif|jpg|png|jpeg';

            foreach($customconfig as $K=>$V) {
                $config[$K] = $V;
            }
            $m = static::$model;
            if (isset($m::$rules['filename'])) {
                foreach ($m::$rules['filename'] as $i=>$rule) {
                    $r = explode('[', rtrim($rule, ']'));
                    if ($r[0] == 'max_length') {
                        $config['max_filename'] = $r[1];
                        continue;
                    }
                }
            }

            $Upload->initialize($config);
            $File = 'im-imageupload';

            if($Upload->do_upload($File)){
                $FileData = $Upload->data();
            }else{
                return json_encode(array('error'=>$Upload->display_errors('','')));
            }

            $Image = new static::$model();

            $Image->filename = $FileData['file_name'];
            $Image->title = $FileData['raw_name'];
            $Image->height = $FileData['image_height'];
            $Image->width = $FileData['image_width'];
            $Image->imagegroup = static::$group;

            if($Image->save()) {
                $FileData['image_path'] = base_url(static::$imageroot.$FileData['file_name']);
                $FileData['title'] = $FileData['raw_name'];
                $FileData['id'] = $Image->id();
                $this->after_save($Image);

                echo json_encode($FileData);
            }else{
                unlink($FileData['full_path']);
                echo json_encode(array('error'=>'Unknown DB Save error'));
            }
        }

        function after_save(&$Image) {
            return;
        }

        function _get_model() {
            $Model = new static::$model();
            $Model->db->order_by('created', 'desc');
            return $Model;
        }

        function insertAfter() {
            $this->after = true;
            return $this;
        }

        function display_picker($DisplayDropper = true, $DisplayImages = true, $Limit = null) {
            $Model = $this->_get_model();
            if (!is_null($Limit)) $Model->db->limit($Limit);

            if (!is_null(static::$group)) $Model->db->where('imagegroup', static::$group);

            $Images = $Model->fetch();
            ?>
            <div class="image-manager"
                <?=$DisplayImages?'im-preview':''?>
                <?if($this->after):?>im-insertafter <?endif;?>
                <?if(isset(static::$api)):?>im-api="<?=static::$api?>" <?endif;?>
                <?if(isset(static::$success)):?>im-success="<?=static::$success?>" <?endif;?>
                >
                <?if($DisplayDropper):?>
                <div class="im-drop">
                    <p><i class="fa fa-cloud-upload"></i>Drop file's here to upload</p>
                </div>
                <div class="im-status">
                </div>
                <?endif;?>

                <?if($DisplayImages):?>
                <div class="im-preview">
                    <?foreach($Images as $Image){
                        $this->display_image($Image);
                    }?>
                </div>
                <?endif;?>
            </div>
            <?
        }

        function display_image($Image) {
            if (!is_a($Image, static::$model)) {
                $Image = new static::$model($Image);
            }
            ?><div class="im-image" image-id="<?=$Image->id()?>">
                <div class="im-container">
                    <img src="<?=base_url(static::$imageroot.$Image->filename)?>"
                        <?if(isset($Image->title)):?>
                        title="<?=$Image->title?>"
                        alt="<?=$Image->title?>"
                        <?endif;?> />
                </div>
                <div class="im-actions">
                    <ul>
                        <li class="im-edit"><i class="fa fa-eye"></i></li>
                        <li im-remove><i class="fa fa-remove"></i></li>
                    </ul>
                </div>
            </div><?
        }

        function after_insert($ImageID) {
            return $this->display_image($ImageID);
        }

        function after_delete($Image){
            return true;
        }

        function delete_image($ImageID) {
            $Image = new static::$model($ImageID);
            if (@unlink(static::$uploadroot.$Image->filename)){
                if ($Image->delete()) {
                    $this->after_delete($Image);
                    return json_encode(array('status'=>200));
                }
            }
            return json_encode(array('status'=>500));
        }
    }

?>
