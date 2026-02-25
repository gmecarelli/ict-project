<?php

namespace Packages\IctInterface\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Models\Attachment;
use Packages\IctInterface\Models\AttachmentArchive;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\Logger;

class AttachmentController extends IctController
{
    public $response;
    public function __construct()
    {
        parent::__construct();
        $this->response = [
            'result' => 'success',
            'message' => 'Allegato eliminato con successo',
        ];
        $this->log = new Logger();
    }

    public function delete() {
        $id = request('attach_id');
        $attach = Attachment::find($id);
        $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__,"rows: ".$attach->get()->count());
        if(isset($attach->id)) {
            $res = Attachment::where('id', $id)
                    ->delete();
            $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__,"id DEL: {$id}");

            if(is_null($res)) {
                DB::rollBack();
                $this->log->rollback(__FILE__,__LINE__);
                $this->response['result'] = 'fail';
                $this->response['message'] = "Impossibile eliminare l'allegato";
                return $this->response;
            }
// dd($attach);            
            $res = AttachmentArchive::where('attach', $attach->attach)
                                    ->where('reference_id', $attach->id)
                                    ->delete();
            if(is_null($res)) {
                DB::rollBack();
                $this->log->rollback(__FILE__,__LINE__);
                $this->response['result'] = 'fail';
                $this->response['message'] = "Impossibile eliminare l'allegato nell'archivio";
                return $this->response;
            }

            $currentPath = getcwd();
            chdir(public_path()."/storage/".config('ict.upload_dir'));
    // dd($attach->attach);
            @unlink($attach->attach);
            chdir($currentPath);
        } else {
            $this->response['result'] = 'fail';
            $this->response['message'] = "Allegato non trovato";
            return $this->response;
        }
        DB::commit();
        $this->log->commit(__FILE__,__LINE__);
        return $this->response;
    }
}
