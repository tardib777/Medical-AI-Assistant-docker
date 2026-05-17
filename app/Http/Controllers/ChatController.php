<?php

namespace App\Http\Controllers;

use App\Models\MedicalSession;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Services\ChatService;
use App\Services\TextCleanService;
use Carbon\Carbon;

class ChatController extends Controller
{
    protected $chatService;
    protected $textCleanService;

    public function __construct(ChatService $chatService, TextCleanService $textCleanService)
    {
        $this->chatService = $chatService;
        $this->textCleanService = $textCleanService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'prompt' => 'required|string|min:1'
        ]);

        $session = $request->user()->sessions()->where('status', 'active')->first();
        if($session->messages->isEmpty()){
            $session->messages()->save(new Message([
                'role' => 'system',
                'content' => 'أنت نظام دعم لاتخاذ القرار الطبي (Medical Decision Support System)، ولست طبيبًا بشريًا ولا تقدم تشخيصًا طبيًا نهائيًا.

                    مهمتك هي تقديم إرشادات طبية أولية فقط، اعتمادًا على:

                    الأعراض التي يذكرها المستخدم

                    السجل الطبي المخزّن للمستخدم (إن وُجد)

                    يُمنع عليك بشكل صارم:

                    تقديم تشخيص نهائي

                    وصف أدوية تتطلب وصفة طبية

                    استبدال أو معارضة رأي طبيب مختص

                    إعطاء تعليمات طبية خطرة أو غير آمنة

                    يجب عليك تنفيذ الخطوات التالية بدقة:

                    تحليل الأعراض الحالية بالربط مع التاريخ الطبي المتاح.

                    تصنيف الحالة إلى واحدة فقط من الفئات التالية:

                    حالة طبية طارئة تتطلب تدخلاً إسعافيًا فوريًا.

                    حالة غير طارئة ولكن تتطلب استشارة طبيب مختص.

                    حالة خفيفة يمكن التعامل معها بنصائح عامة والراحة وأدوية بدون وصفة.

                    إذا وُجدت مؤشرات طبية طارئة:

                    أبلغ المستخدم بوضوح وحزم بضرورة طلب المساعدة الطبية الطارئة فورًا.

                    إذا لم تكن الحالة طارئة:

                    قدّم إرشادات صحية عامة وآمنة لتخفيف الأعراض.

                    حدّد بوضوح متى يجب مراجعة الطبيب في حال استمرار الأعراض أو تفاقمها.

                    قواعد الأسلوب:

                    استخدم لغة واضحة، هادئة، ومحايدة.

                    لا تستخدم لغة تشخيصية قطعية.

                    أعطِ الأولوية القصوى لسلامة المريض.

                    احترم الخصوصية ولا تطلب معلومات شخصية غير ضرورية.

                    عند الشك، اختر دائمًا الخيار الأكثر أمانًا للمستخدم
                    هذا الملف الطبي للمريض: ' . json_encode($request->user()->profile)
            ]),
            new Message([
                'role' => 'user',
                'content' => 'أنا مريض وهذا هو ملفي الطبي:' . json_encode($request->user()->profile)
            ]));
        }
        $response = $this->chatService->chat(
            $session->messages()->select('role', 'content', 'created_at')->get()->toArray(),
            $request->prompt,
            $session->id
        );

        $cleanedContent = $this->textCleanService->clean($response["content"]);

        return response()->json([
            'reply' => [
                'content' => $cleanedContent,
                'role' => $response["role"],
                'timestamp' => Carbon::now()->toDateTimeString()
            ]
        ]);
    }
}
