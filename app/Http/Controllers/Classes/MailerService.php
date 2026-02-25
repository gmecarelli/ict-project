<?php

namespace App\Http\Controllers\Classes;

use Exception;
use Illuminate\Http\Request;


use Illuminate\Mail\Message;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class MailerService extends Controller
{


    /**
     * Invia un'email tramite il comando mutt, utilizzando una vista Blade per
     * creare il contenuto dell'email.
     *
     * @param string|array $to      Destinatario/i dell'email
     * @param string        $subject Oggetto dell'email
     * @param string        $body    Contenuto dell'email
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendEmailMutt($to, $subject, $body)
    {

        $from = "[ALPHA 2.0] - noReply"; // Mittente
        //  $body = json_encode($body, JSON_PRETTY_PRINT);
        // Crea il contenuto dell'email usando la vista Blade (se necessario)
        $emailContent = View::make('emails.general', [
            'subject' => $subject,
            'body' => $body,
        ])->render();

        // Salva il contenuto in un file temporaneo
        $tempBodyFile = tempnam(sys_get_temp_dir(), 'mutt_email_');
        file_put_contents($tempBodyFile, $emailContent);

        // Converti l'array di destinatari in una stringa separata da spazi (se necessario)
        if (is_array($to)) {
            $to = implode(' ', $to);
        }

        // Comando mutt con header personalizzato
        $command = "mutt -e 'set content_type=text/html' 'my_hdr From:{$from}' -s '{$subject}' {$to} < {$tempBodyFile}";
        shell_exec($command);

        // Rimuovi il file temporaneo
        unlink($tempBodyFile);

        return response()->json(['message' => 'Email inviata con successo tramite mutt.']);
    }

    /**
     * Invia un'email utilizzando la configurazione di default per mittente e destinatario.
     *
     * @param string $subject Oggetto dell'email
     * @param array $data Dati da passare alla view
     * @param string $view Nome della view da utilizzare per creare il contenuto dell'email
     * @param array $attachments Elenco di file da allegare all'email
     * @param string|array $to Indirizzo/i email del destinatario
     * @param string $from Indirizzo email del mittente
     * @param string $fromName Nome del mittente
     * @param array $cc copia conoscenza
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendEmail($subject, $data = [], $view = 'emails.general',  $attachments = [], $to = [],  $from = null,$replyTo = null, $fromName = null, $cc = null)
    {
        try {


            // Usa i valori di default se non specificati
            $from = $from ?: config('mail.from.address');
            $fromName = $fromName ?: null;
            $to = $to ?: config('mail.custom_to.address');
            $replyTo = $replyTo ? Mail::alwaysReplyTo($replyTo) : null;
           
            $data['subject'] = $subject;
            
            Mail::send($view, $data, function ($message) use ($to, $subject, $from, $fromName, $attachments, $cc) {
                // Aggiungi mittente e oggetto
                $message->from($from, $fromName)
                    ->subject($subject);



                // Aggiungi destinatari
                $message->to($to, null, true);

                // Aggiungi CC solo se è popolato
                if (!empty($cc) || $cc != null) {
                   $message->cc($cc);
                }

                // Aggiungi allegati se presenti
                foreach ($attachments as $attachment) {
                    $message->attach($attachment);
                }
            });

            return response()->json([
                'result' => 'success',
                'message' => 'Email inviata con successo'
            ]);
        } catch (Exception $e) {
            return response()->json(
               [
                'result' => 'error',
                'message' => $e->getMessage()
               ]);
        }
    }
}
