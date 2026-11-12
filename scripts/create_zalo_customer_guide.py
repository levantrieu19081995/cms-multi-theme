from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn

OUT = "docs/HUONG_DAN_CAU_HINH_ZALO_CHO_KHACH_HANG.docx"
BLUE="0B57D0"; LIGHT="EAF2FF"; PALE="F5F9FF"; BORDER="D9D9D9"; DARK="1F2937"; GREEN="E8F5E9"; ORANGE="FFF2CC"

def font(r,size=10.5,bold=False,color="000000",name="Aptos",italic=False):
    r.font.name=name; r.font.size=Pt(size); r.font.bold=bold; r.font.italic=italic; r.font.color.rgb=RGBColor.from_string(color)
    rp=r._element.get_or_add_rPr()
    for k in ("ascii","hAnsi","eastAsia"): rp.rFonts.set(qn("w:"+k),name)

def shade(c,fill):
    p=c._tc.get_or_add_tcPr(); x=p.find(qn("w:shd")) or OxmlElement("w:shd"); x.set(qn("w:fill"),fill)
    if x.getparent() is None:p.append(x)

def border(c):
    p=c._tc.get_or_add_tcPr(); b=p.first_child_found_in("w:tcBorders") or OxmlElement("w:tcBorders")
    if b.getparent() is None:p.append(b)
    for e in ("top","left","bottom","right","insideH","insideV"):
        x=b.find(qn("w:"+e)) or OxmlElement("w:"+e); x.set(qn("w:val"),"single"); x.set(qn("w:sz"),"6"); x.set(qn("w:color"),BORDER)
        if x.getparent() is None:b.append(x)

def pad(c):
    p=c._tc.get_or_add_tcPr(); m=p.first_child_found_in("w:tcMar") or OxmlElement("w:tcMar")
    if m.getparent() is None:p.append(m)
    for k in ("top","start","bottom","end"):
        x=m.find(qn("w:"+k)) or OxmlElement("w:"+k); x.set(qn("w:w"),"120"); x.set(qn("w:type"),"dxa")
        if x.getparent() is None:m.append(x)

def para(d,text="",style=None,bold_prefix=None,after=5,align=None):
    p=d.add_paragraph(style=style)
    if align is not None:p.alignment=align
    p.paragraph_format.space_after=Pt(after); p.paragraph_format.line_spacing=1.1
    if bold_prefix and text.startswith(bold_prefix):
        r=p.add_run(bold_prefix);font(r,bold=True);r=p.add_run(text[len(bold_prefix):]);font(r)
    else:r=p.add_run(text);font(r)
    return p

def bullets(d,items):
    for x in items:
        p=d.add_paragraph(style="List Bullet");p.paragraph_format.space_after=Pt(2);r=p.add_run(x);font(r,size=10.2)

def numbers(d,items):
    for x in items:
        p=d.add_paragraph(style="List Number");p.paragraph_format.space_after=Pt(3);r=p.add_run(x);font(r,size=10.2)

def table(d,headers,rows,fs=9.4):
    t=d.add_table(rows=1,cols=len(headers));t.alignment=WD_TABLE_ALIGNMENT.CENTER
    for i,x in enumerate(headers):
        c=t.rows[0].cells[i];shade(c,BLUE);border(c);pad(c);r=c.paragraphs[0].add_run(x);font(r,size=fs,bold=True,color="FFFFFF")
    for ri,row in enumerate(rows):
        row=list(row)[:len(headers)]+[""]*max(0,len(headers)-len(row));cs=t.add_row().cells
        for i,x in enumerate(row):
            c=cs[i];border(c);pad(c);c.vertical_alignment=WD_CELL_VERTICAL_ALIGNMENT.CENTER
            if ri%2:shade(c,PALE)
            r=c.paragraphs[0].add_run(str(x));font(r,size=fs)
    d.add_paragraph().paragraph_format.space_after=Pt(1)
    return t

def step(d,n,title,text):
    p=d.add_paragraph();p.paragraph_format.space_before=Pt(7);p.paragraph_format.space_after=Pt(2)
    r=p.add_run("Bước "+str(n)+"  ");font(r,size=11,bold=True,color=BLUE);r=p.add_run(title);font(r,size=11,bold=True,color=DARK)
    para(d,text,after=4)

def link(d,label,url):
    p=d.add_paragraph();r=p.add_run(label+": ");font(r,bold=True,size=9.5);rid=p.part.relate_to(url,"http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink",is_external=True)
    h=OxmlElement("w:hyperlink");h.set(qn("r:id"),rid);rr=OxmlElement("w:r");rp=OxmlElement("w:rPr");co=OxmlElement("w:color");co.set(qn("w:val"),BLUE);rp.append(co);u=OxmlElement("w:u");u.set(qn("w:val"),"single");rp.append(u);rr.append(rp);tt=OxmlElement("w:t");tt.text=url;rr.append(tt);h.append(rr);p._p.append(h)

d=Document();s=d.sections[0];s.page_width=Inches(8.5);s.page_height=Inches(11);s.top_margin=Inches(.65);s.bottom_margin=Inches(.6);s.left_margin=Inches(.7);s.right_margin=Inches(.7)
for nm,sz,col in (("Title",23,"000000"),("Heading 1",15,"000000"),("Heading 2",12,BLUE)):
    st=d.styles[nm];st.font.name="Aptos";st.font.size=Pt(sz);st.font.bold=True;st.font.color.rgb=RGBColor.from_string(col);st._element.rPr.rFonts.set(qn("w:ascii"),"Aptos");st._element.rPr.rFonts.set(qn("w:hAnsi"),"Aptos");st.paragraph_format.space_before=Pt(10);st.paragraph_format.space_after=Pt(5)

p=d.add_paragraph(style="Title");p.alignment=WD_ALIGN_PARAGRAPH.CENTER;r=p.add_run("Hướng dẫn cấu hình Zalo OA cho khách hàng");font(r,name="Aptos Display",size=23,bold=True)
p=d.add_paragraph();p.alignment=WD_ALIGN_PARAGRAPH.CENTER;r=p.add_run("Tài liệu dành cho người tạo tài khoản và cấu hình kết nối CMS");font(r,size=11,color=BLUE)
t=d.add_table(rows=1,cols=5);t.alignment=WD_TABLE_ALIGNMENT.CENTER
for i,(a,b) in enumerate((("1","Tạo OA"),("2","Chọn gói\nTăng trưởng"),("3","Tạo App"),("4","Xác minh\ndomain"),("5","Cấp quyền\nkết nối"))):
    c=t.rows[0].cells[i];shade(c,LIGHT if i%2==0 else GREEN);border(c);pad(c);p=c.paragraphs[0];p.alignment=WD_ALIGN_PARAGRAPH.CENTER;r=p.add_run(a+"\n");font(r,size=15,bold=True,color=BLUE);r=p.add_run(b);font(r,size=9,bold=True,color=DARK)
para(d,"Tài liệu này không yêu cầu người cấu hình biết lập trình hoặc gọi API. Chỉ cần thực hiện đúng các bước trên Zalo và cung cấp đúng thông tin cho đội triển khai CMS.",align=WD_ALIGN_PARAGRAPH.CENTER,after=8)
p=d.add_paragraph();p.alignment=WD_ALIGN_PARAGRAPH.CENTER;r=p.add_run("Áp dụng cho triển khai mới và khách hàng mới");font(r,size=9,color="666666",italic=True)
d.add_page_break()

d.add_heading("1. Ai thực hiện và cần chuẩn bị gì",level=1)
table(d,["Người thực hiện","Nhiệm vụ"],[("Khách hàng / chủ OA","Tạo OA, hoàn thiện thông tin doanh nghiệp, chọn gói Tăng trưởng và cấp quyền cho ứng dụng."),("Đơn vị triển khai CMS","Tạo hoặc cấu hình Zalo App, xác minh domain, nhập App ID/Secret Key vào server và kiểm tra kết nối."),("Người quản trị website","Cung cấp quyền cập nhật domain hoặc phối hợp đặt file/thẻ xác minh trên website.")])
bullets(d,["Một số điện thoại/email có thể đăng nhập Zalo.","Tên doanh nghiệp, logo, ảnh bìa, mô tả, ngành nghề và thông tin liên hệ.","Quyền quản trị domain website của khách hàng.","Website đã chạy HTTPS, ví dụ https://tenmiencuakhachhang.vn.","Tên OA mong muốn và người chịu trách nhiệm quản trị OA."])

d.add_heading("2. Tạo Zalo Official Account",level=1)
step(d,1,"Mở trang OA","Truy cập https://oa.zalo.me hoặc https://oa.zaloapp.com, đăng nhập bằng tài khoản Zalo của người đại diện/quản trị.")
step(d,2,"Tạo Official Account","Chọn Tạo Official Account mới, chọn loại phù hợp với doanh nghiệp và nhập tên OA, ngành nghề, mô tả, ảnh đại diện, ảnh bìa.")
step(d,3,"Hoàn thiện thông tin","Điền địa chỉ, số điện thoại, website và thông tin doanh nghiệp. Tên OA nên thống nhất với tên thương hiệu trên website.")
step(d,4,"Xác minh OA nếu được yêu cầu","Thực hiện theo yêu cầu của Zalo để OA có trạng thái xác thực. Việc xác thực giúp tăng độ tin cậy và có thể là điều kiện cho một số quyền/tính năng.")
para(d,"Sau khi tạo, vào Danh sách OA của tôi và ghi lại OA ID. OA ID là mã của kênh Zalo; không phải App ID của ứng dụng.",bold_prefix="Lưu ý: ")

d.add_heading("3. Chọn gói Tăng trưởng",level=1)
para(d,"Để triển khai đăng bài và sử dụng các quyền mở rộng, khách hàng nên chọn gói Tăng trưởng theo bảng giá hiện hành của Zalo. Tên gói, giá và điều kiện có thể thay đổi theo thời điểm nên cần kiểm tra trực tiếp trên OA Manager.")
step(d,1,"Mở phần gói dịch vụ","Trong OA Manager, chọn OA cần cấu hình rồi mở mục Gói dịch vụ hoặc Bảng giá.")
step(d,2,"Chọn gói Tăng trưởng","Chọn gói Tăng trưởng, đọc quyền lợi, hạn mức và thời hạn, sau đó thực hiện thanh toán theo hướng dẫn của Zalo.")
step(d,3,"Kiểm tra trạng thái","Sau khi thanh toán, kiểm tra OA đã hiển thị gói Tăng trưởng và các quyền quản lý bài viết cần thiết.")
para(d,"Nếu chỉ cần thử kết nối lấy thông tin OA, có thể chưa cần nâng gói. Nếu cần đăng bài từ CMS, hãy hoàn thiện gói và quyền trước khi nghiệm thu.",bold_prefix="Khuyến nghị: ")

d.add_heading("4. Tạo ứng dụng trên Zalo Developers",level=1)
step(d,1,"Mở Zalo Developers","Truy cập https://developers.zalo.me và đăng nhập bằng tài khoản được giao quản lý ứng dụng.")
step(d,2,"Tạo App mới","Chọn Quản lý ứng dụng → Tạo ứng dụng mới. Đặt tên dễ nhận biết, ví dụ TênKháchHàng CMS.")
step(d,3,"Nhập thông tin ứng dụng","Nhập tên hiển thị, số điện thoại liên hệ và tải icon ứng dụng theo yêu cầu.")
step(d,4,"Ghi lại ID ứng dụng","Mở Thông tin ứng dụng và sao chép ID ứng dụng. Đây là App ID sẽ gửi cho đội triển khai CMS.")
table(d,["Thông tin","Dùng để làm gì","Ai được giữ"],[("App ID","Nhận diện ứng dụng; có thể cung cấp cho đội triển khai.","Khách hàng và đội triển khai."),("Secret Key","Khóa bí mật của ứng dụng; dùng để cấu hình máy chủ.","Chỉ người phụ trách kỹ thuật/triển khai; không gửi công khai."),("OA ID","Nhận diện Official Account sẽ cấp quyền.","Khách hàng và đội triển khai.")])
para(d,"Không gửi Secret Key qua nhóm chat đông người, không chụp chung với thông tin đăng nhập và không đưa vào tài liệu bàn giao công khai.",bold_prefix="Bảo mật: ")

d.add_heading("5. Lấy Secret Key",level=1)
step(d,1,"Chọn đúng ứng dụng","Trong Zalo Developers, mở đúng App vừa tạo.")
step(d,2,"Mở phần cài đặt","Vào Cài đặt hoặc Thông tin ứng dụng, tìm mục Secret Key/Khóa bí mật.")
step(d,3,"Sao chép và bàn giao an toàn","Bấm sao chép rồi gửi qua kênh bảo mật cho người triển khai. Không gửi nhầm Secret Key của App khác.")
para(d,"Nếu nghi ngờ Secret Key bị lộ, báo ngay cho người triển khai để đổi/thu hồi khóa theo khả năng mà giao diện Zalo cung cấp.",bold_prefix="Nếu bị lộ khóa: ")

d.add_heading("6. Đăng ký và xác minh domain website",level=1)
para(d,"Domain dùng để chứng minh website thuộc quyền quản lý của khách hàng và cho phép Zalo chấp nhận callback kết nối. Domain phải là domain thật của khách hàng, có HTTPS và truy cập được từ Internet.")
step(d,1,"Mở Xác thực domain","Trong Zalo Developers, chọn ứng dụng → Xác thực domain, nhập tên miền không có http:// hoặc https://.")
step(d,2,"Chọn cách xác minh","Có thể chọn DNS TXT, tải file HTML hoặc thêm thẻ meta vào trang chủ website.")
step(d,3,"Phối hợp với người quản trị website","Gửi token/file Zalo cấp cho người quản trị website. Nếu dùng meta tag, đặt trong phần <head> của trang chủ.")
step(d,4,"Bấm Xác thực","Sau khi website đã cập nhật, quay lại Zalo Developers và bấm Xác thực. Khi thành công, không xóa thẻ meta, file HTML hoặc bản ghi DNS.")
table(d,["Cách xác minh","Ai thực hiện","Khi nên dùng"],[("DNS TXT","Người quản lý DNS/domain","Phù hợp khi có quyền quản trị DNS."),("File HTML","Người quản trị server/website","Phù hợp khi có quyền tải file vào thư mục public."),("Thẻ meta","Người quản trị website","Phù hợp khi có thể sửa giao diện trang chủ.")])
para(d,"Token xác minh là riêng cho từng App và domain. Không lấy token của khách hàng này dùng cho khách hàng khác.",bold_prefix="Lưu ý: ")

d.add_heading("7. Khai báo địa chỉ callback cho đội triển khai",level=1)
para(d,"Người cấu hình không cần hiểu API. Chỉ cần cung cấp cho đội triển khai domain website, OA ID, App ID và Secret Key qua kênh bảo mật. Đội triển khai sẽ gửi lại một địa chỉ callback để khai báo trong Zalo Developers.")
step(d,1,"Nhận callback từ đội triển khai","Ví dụ: https://tenmiencuakhachhang.vn/cms/truyen-thong/tai-khoan/zalo/callback")
step(d,2,"Khai báo đúng địa chỉ","Trong phần Official Account API của App, nhập đúng callback mà đội triển khai cung cấp rồi bấm Lưu.")
step(d,3,"Không tự sửa callback","Nếu đổi domain hoặc đường dẫn callback, phải báo đội triển khai cập nhật đồng thời cả Zalo và CMS.")

d.add_heading("8. Cấp quyền cho ứng dụng kết nối OA",level=1)
numbers(d,["Đăng nhập OA Manager bằng tài khoản có quyền quản trị OA.","Mở CMS và chọn chức năng kết nối Zalo OA.","Zalo hiển thị tên ứng dụng và tên OA; kiểm tra đúng hai thông tin này.","Mở các nhóm quyền, chọn quyền quản lý thông tin OA và quyền quản lý bài viết nếu khách hàng cần đăng bài.","Tích đồng ý điều khoản rồi bấm Cấp quyền.","Chờ CMS chuyển về và hiển thị Đã kết nối."])
para(d,"Nếu Zalo báo đã đạt giới hạn số ứng dụng, vào OA Manager → Quản lý tài khoản → Quản lý liên kết, thu hồi các ứng dụng cũ không còn dùng. Chỉ thu hồi ứng dụng, không chọn Thôi làm Admin.",bold_prefix="Nếu gặp giới hạn app: ")

d.add_heading("9. Kiểm tra sau khi cấu hình",level=1)
table(d,["Nội dung cần kiểm tra","Kết quả đạt"],[("OA","Đúng tên, logo, thông tin doanh nghiệp và OA ID."),("Gói dịch vụ","Hiển thị gói Tăng trưởng nếu khách hàng đã mua."),("Domain","Đã xác minh và không xóa thẻ/file/DNS xác minh."),("Ứng dụng","Đúng App ID; Secret Key đã được lưu ở nơi an toàn."),("Kết nối CMS","Hiển thị Đã kết nối, đúng OA và số người theo dõi."),("Đăng bài thử","Bài chuyển sang Đã đăng hoặc Đang xử lý; không lặp lại thao tác nhiều lần.")])
bullets(d,["Chụp lại màn hình trạng thái OA và gói dịch vụ.","Lưu OA ID và App ID trong biên bản bàn giao.","Không đưa Secret Key vào biên bản công khai; chỉ xác nhận đã bàn giao qua kênh bảo mật.","Ghi tên người giữ quyền quản trị OA và người giữ quyền quản lý App."])

d.add_heading("10. Quy trình khi khách hàng đổi website hoặc đổi người quản trị",level=1)
bullets(d,["Đổi domain: xác minh domain mới, cập nhật callback mới và báo đội triển khai sửa cấu hình CMS.","Đổi người quản trị: bàn giao quyền OA Manager và quyền Zalo Developers; kiểm tra lại người nhận Secret Key.","Đổi App: tạo App mới, lấy App ID/Secret Key mới, xác minh domain lại nếu Zalo yêu cầu, sau đó cấp quyền OA cho App mới.","Không xóa App cũ ngay nếu CMS còn đang dùng token của App đó; hãy chuyển kết nối và kiểm tra trước."])

d.add_heading("11. Checklist bàn giao cho khách hàng",level=1)
table(d,["Hạng mục","Đã xong"],[("Đã tạo OA và hoàn thiện thông tin.","☐"),("Đã chọn gói Tăng trưởng nếu cần đăng bài.","☐"),("Đã tạo Zalo App.","☐"),("Đã ghi lại App ID và OA ID.","☐"),("Đã lấy Secret Key và bàn giao bảo mật.","☐"),("Đã xác minh domain website.","☐"),("Đã khai báo callback do đội triển khai cung cấp.","☐"),("Đã cấp quyền cho ứng dụng.","☐"),("CMS hiển thị Đã kết nối.","☐"),("Đã thử một bài đăng và kiểm tra kết quả.","☐")])

d.add_heading("12. Địa chỉ cần dùng",level=1)
link(d,"Zalo Official Account","https://oa.zalo.me")
link(d,"Zalo Developers","https://developers.zalo.me")
link(d,"Tài liệu xác minh domain","https://docs.zaloplatforms.com/docs/OA/phu-luc/huong-dan-xac-thuc-domain")
link(d,"Tài liệu khởi tạo ứng dụng","https://docs.zaloplatforms.com/docs/OA/bat-dau/khoi-tao-ung-dung")
for s in d.sections:
    p=s.footer.paragraphs[0];p.alignment=WD_ALIGN_PARAGRAPH.CENTER;r=p.add_run("Hướng dẫn cấu hình Zalo OA cho khách hàng");font(r,size=8,color="666666")
d.core_properties.title="Hướng dẫn cấu hình Zalo OA cho khách hàng"
d.core_properties.subject="Tạo OA, chọn gói Tăng trưởng, tạo App, lấy khóa và xác minh domain"
d.core_properties.author="CMS Team"
d.save(OUT)
print(OUT)

