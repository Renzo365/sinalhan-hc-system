Yes, I can understand it. The transcript is informal and has some parts where the wording is incomplete or the speaker changes topic, but there is enough context to reconstruct the important points.

The biggest thing I noticed is that the client clarified **how the paper IHP is actually used in practice**, which is slightly different from how the form may look at first glance. In particular, **Pages 1–3 are mainly for the patient's initial interview/intake, while the consultation portion is repeatedly used for succeeding visits**. 

## 1. IHP / Initial Patient Information

### What the client explained

For a **new patient who has no existing record**, the staff interviews the patient and completes the IHP information. This includes the information on the first pages, such as demographics, medical history, family history, and other interview questions. The client emphasized that this is done for a new patient because there is a lot of information to ask and write down. 

Once the patient has an existing record, they do **not create a new IHP every time they return**. The same IHP remains associated with that patient. Even if the patient returns after several years, the staff searches for the existing record instead of making another one. 

### Module I think this belongs to

**Patient Registration / Patient Profile / IHP**

For your system, this supports the idea that:

> **One patient should have one main IHP/Health Record profile, rather than a new IHP for every visit.**

---

# 2. Patient Record Number / Envelope Number

This was an important part of the interview.

The health center currently gives the patient's physical record envelope a **number**. The same number is also recorded in their logbook.

For example:

* Last patient/envelope = 1000
* New patient = 1001
* 1001 is written on the envelope
* 1001 is also recorded in the logbook

When the patient returns, the staff can search the logbook and use the number to find the patient's physical envelope. 

The client later confirmed that the number is placed on the **envelope itself**, and that it is essentially a sequential number. 

### Module

**Patient Registration / Patient Directory / Record Identification**

### Important system implication

Your digital system probably needs something equivalent to a:

**Patient Record Number / Health Record Number**

It could serve as the digital replacement for the envelope/logbook identification system.

---

# 3. IHP Pages 1–3 Are Mainly Initial Interview Information

This was one of the clearest parts of the interview.

The client confirmed that the first several pages are completed when a patient is **new and has no existing record**. They ask the patient the information during the interview. 

That includes things such as:

* Personal/demographic information
* Medical history
* Surgical history question
* Family history
* Lifestyle questions
* Immunization history
* Other information contained in the IHP

The client also said that the age category is checked, but the **actual specific age is also written**. For example, they may check the 16–24 age category and then write "17 years old." 

### Module

**Patient Registration / IHP**

---

# 4. Pertinent Physical Examination / Vital Signs in IHP

This part is particularly important for your current system design.

The client explained that the physical examination/vital information appearing on the IHP is associated with the **initial visit/interview**.

For example, the IHP may contain:

* BP
* Height
* Weight
* Other physical examination information

But this is not where they continuously record vital signs for every future visit.

When the patient comes back later, the staff records the new vital signs in the **consultation record instead**. 

The client specifically said that if the patient returns after a long period, even after a year, the new BP, temperature, and similar measurements are written in the consultation portion. 

### Module

**IHP → Initial Physical Examination**

and

**Consultation → Visit Vital Signs**

### This is an important distinction

I would model it as:

**Initial registration**
→ Initial physical examination / baseline information

**Every subsequent consultation**
→ New vital signs recorded for that visit

This is different from treating the IHP page 2 vital signs section as a continuously updated vital-sign history.

---

# 5. Consultation Is the Repeated Record

The client explained that after the patient's initial interview, the consultation section becomes the part that gets used repeatedly.

When the patient returns:

* Date is recorded
* Complaint/reason for consultation is recorded
* Age is recorded
* Weight/height and other examination information may be recorded
* Vital signs are recorded
* The consultation information continues to be added for each visit

When the available space is exhausted, an extension is added to continue the record. 

The client described it as repeatedly adding new entries until the paper is full.

### Module

**Consultation / Clinical Encounter History**

This strongly supports having a **separate consultation history** in your system instead of constantly editing the main IHP.

---

# 6. IHP Is Not Recreated Every Year

The client explicitly answered **no** when asked whether a new page/IHP is created every year.

They said it is basically **one record for the patient** and they continue using it. 

### Module

**IHP / Patient Health Record**

### Important system implication

You should probably avoid designing:

> 2025 IHP → 2026 IHP → 2027 IHP

unless the actual health center has another requirement for that.

Instead, the structure seems closer to:

> **One patient profile/IHP**
>
> * **many consultation records**
> * **additional specialized records where applicable**

---

# 7. PCB Page 3 / Quarterly Services

The first part of the interview was about the **PCB Page 3**, particularly the quarterly structure.

The client explained that the year is divided into four quarters:

* January–March
* April–June
* July–September
* October–December

They use the quarterly sections to record the applicable service information, including BP-related information. 

The exact beginning of the transcript is cut off, so I would **not make assumptions about everything on that page**.

### Module

**IHP / PCB Information**

Potentially this could be a **PCB/Service Tracking section** inside the patient's health record.

---

# 8. Diagnostic Examination / PCB Services Section

You asked the client about the **Diagnostic Examination & PCB Services Encounter** section.

The client's answer was very direct:

> They do not really fill it out.

They said they basically do not write in that section. but you may keep this just in case.

### Module

**IHP / PCB**



---

# 9. Maternal / Prenatal Record

The client explained that the **pregnancy history in the IHP and the pregnancy history in the prenatal record contain the same information**.

The prenatal record and IHP are essentially connected together, and when the patient is pregnant, the **prenatal record becomes the more actively used document**. The client described the prenatal record as being placed on top because it is used more than the IHP during pregnancy. 

### Module

**Maternal Care / Prenatal Care**

### Very important relationship

This suggests:

**Pregnant patient**
→ still has an IHP
→ additionally has a Prenatal/Maternal Record

So prenatal care should **not replace the patient's main IHP**.

It is an additional clinical record linked to the same patient.

That matches the way you've been thinking about having a regular IHP plus specialized maternal records.

---

# 10. Pregnancy History and GTPAL Information

The client explained that information in the IHP's pregnancy history is used as the basis for the pregnancy information in the prenatal record.

They specifically discussed:

* G = number of pregnancies
* P = parity/deliveries
* Full-term pregnancies
* Premature pregnancies
* Abortions
* Living children

They explained that the information in the prenatal record comes from the pregnancy history recorded in the IHP. 

### Module

**Maternal Care / Prenatal Care**

### System implication

You could have:

**IHP Pregnancy History**

as the patient's general historical information,

then:

**Prenatal Record**

for the current pregnancy and prenatal follow-ups.

---

# 11. Prenatal Follow-up Tracking

The prenatal record is not just an initial form.

The client said the prenatal table continues onto another page when needed. The midwife writes remarks and the BHW records vital information in the relevant parts. 

They also explained that the schedule of prenatal visits becomes more frequent as the pregnancy progresses:

* Early pregnancy → generally monthly
* Later pregnancy → every 3 weeks
* Closer to delivery → every 2 weeks
* Very near delivery → weekly



### Module

**Maternal Care / Prenatal Follow-ups**

This is important because prenatal care should probably not be represented as just one static record. It has **multiple follow-up encounters**.

---

# 12. Prenatal AOG and Measurements

The client mentioned several pieces of information recorded during prenatal care:

* AOG / age of gestation
* BP
* Weight
* Abdominal/fundal measurement
* Other follow-up information
* Midwife's remarks



### Module

**Maternal Care / Prenatal Visit**

This supports having something like:

**Prenatal Visit #1**
**Prenatal Visit #2**
**Prenatal Visit #3**

rather than keeping everything in one giant editable form.

---

# 13. Well Baby Record

The client said the well-baby record is used for children **up to five years old**. 

The well-baby record has a continuation, so the record can extend when necessary. 

### Module

**Well Baby Care**

---

# 14. Baby Card and Transfer / Lost Record Situation

This was an important operational detail.

The baby has their own **baby card** containing their records.

The health center also maintains information about the baby.

If the parent loses their baby card and comes to the health center to have the record reconstructed, the staff can use the health center's information.

The client also mentioned **transferees**. 

### Module

**Well Baby Care / Immunization History**

### System implication

Your system may need to support viewing historical immunization information even when the patient's physical card is unavailable.

---

# 15. Well Baby Immunization Tracking

The client clarified that the immunization information in the well-baby record is used to track vaccines.

They mentioned things like:

* Penta 1
* Penta 2
* Other vaccine entries
* Continuation of the vaccination record

They also said vitamin A and deworming are included in the well-baby information. 

### Module

**Well Baby Care / Immunization**

---

# 16. IHP Immunization Is Different From Well Baby Immunization

This is another important distinction.

The client explained that the **IHP immunization section is more of a history/checklist**, rather than the detailed vaccination tracker used for the baby.

For example, when an older child comes in, the staff may ask what vaccines they have already received and check the appropriate items.

They also mentioned adult vaccines, including COVID-19 vaccines, which may be written under other/specified vaccine information. 

### So there appear to be two concepts:

**IHP Immunization History**
→ General vaccine history/checklist

**Well Baby Immunization**
→ Detailed vaccination tracking for the child

### Module

**IHP → Immunization History**

**Well Baby → Immunization Schedule/Tracking**

This distinction is worth preserving in your system.

---

# 17. Past Medical History

There was some confusion in the interview because you asked about past surgical history, but the client answered mostly in terms of **past medical history**.

They talked about checking whether the patient previously had conditions such as:

* TB
* Asthma
* Cancer
* Other illnesses

The purpose is to help the staff understand the patient's previous medical conditions when the patient comes for consultation. 

The client also acknowledged that the form asks whether the patient has previously undergone surgery. 

### Module

**IHP → Medical History**

**IHP → Surgical History**

But I would not assume from this interview that the surgical-history structure should contain only two entries. The client was apparently answering a different part of the question.

---

# 18. Family History

The client explained that the family history section focuses on the patient's **mother and father**.

The purpose is to identify illnesses that may have a hereditary/familial relationship, such as:

* TB
* Lung cancer
* Asthma
* Other illnesses

They explained that the information is recorded according to whether it came from the mother or father. 

### Module

**IHP → Family History**

---

# 19. Lifestyle / Social History

Near the end of the transcript, the client discussed questions about:

* Smoking
* Alcohol consumption
* Drug use

For alcohol, they ask approximately how many bottles the patient consumes during a drinking session. They also ask whether the patient uses drugs. 

### Module

**IHP → Social / Lifestyle History**

This is another indication that the first-time interview collects fairly broad information.

---

# 20. Queue Management

The queue process was very straightforward.

The client explicitly said:

**First Come, First Served.**

There is **no priority system** currently used.

The first person who arrives receives number one, followed by the next person, and so on. 

### Module

**Queue Management**

### Very important

This means you should **not assume senior citizens, PWDs, pregnant patients, etc. automatically get queue priority** based on this interview.

At least according to this client interview, the current process is:

> **First come, first served.**

---

# 21. TCB / To Come Back

The client clarified that **TCB (To Come Back)** is used particularly for:

* Well Baby
* Prenatal

For prenatal care, the return schedule depends on the stage of pregnancy, becoming more frequent closer to delivery. 

### Module

**Prenatal → Follow-up / Return Schedule**

**Well Baby → Follow-up / Return Schedule**

This is slightly different from a normal appointment. It appears to be a **clinical instruction for when the patient should return**.

That distinction could be useful when designing your system.

---

# 22. Weekly Health Center Schedule

The health center has different services assigned to particular days:

| Day       | Activity mentioned  |
| --------- | ------------------- |
| Monday    | Consultation        |
| Tuesday   | Consultation        |
| Wednesday | Well Baby           |
| Thursday  | Pregnant / Prenatal |
| Friday    | Reporting           |

The client explained that Friday is used for reporting the patients they consulted during the week. 

### Module

**Appointments / Schedule**

and

**Reports**

This is useful because the system's schedule shouldn't necessarily be treated as one generic appointment calendar. The center has **service-day scheduling**.

---

# 23. Reporting and TCL

The client said that after consultations, they report the patients they handled.

There are coordinators for particular services. For example, the well-baby coordinator records the patients seen on Wednesday in their **TCL**. 

### Module

**Reports / Service Reporting**

The transcript does not give enough information to confidently define everything contained in TCL, so I would mark that as something requiring further clarification before implementing a detailed TCL module.

---

# 24. Staff / Midwife / Nurse Roles

The client explained that the health center is mainly handled by:

* Midwife
* Nurse

The nurse rotates between the two Sinalhan health centers.

Sinalhan apparently has **two health centers**:

* Sinalhan 1 → Puroks 1, 2, 3
* Their health center → Puroks 4, 5, 6



### Module

**User Roles / Staff Management**

and potentially:

**Health Center / Coverage Area**

This could become relevant later, but I wouldn't automatically add a complicated multi-health-center feature unless the project's scope requires it.

---

# 25. Family Planning

You asked whether the prenatal record is also used for family planning.

The client's answer was that **consultation is used for family planning as well**. 

### Module

**Consultation**

This suggests family planning may not require its own completely separate clinical record in the current workflow.

---

# 26. Referral

The client said referral uses a **separate pre-printed form**, particularly for referral to CHO1.

They specifically said that this referral form does **not need to be recorded inside the computer system**, because it is already a separate form and is not considered part of what they need to encode digitally.

They emphasized that the information they particularly need recorded in the computer is related to **check-ups/consultations**. 

### Module

**Referral**

### But based on the interview:

**Probably out of scope for the digital system**, unless your project requirements say otherwise.

This is a good example where the client directly told you something **does not need digitization**.

---

# 27. The Overall Workflow I Hear From the Client

Putting the interview together, the current process seems to be approximately:

**New Patient**

→ Interview patient
→ Complete IHP information
→ Assign record/envelope number
→ Put physical record in envelope
→ Record number in logbook
→ Patient can now return using the existing record

Then:

**Returning Patient**

→ Find existing record
→ Use consultation section
→ Record complaint
→ Record examination/vital signs
→ Continue consultation history

Then, depending on the patient's situation:

**Pregnant**

→ IHP remains
→ Prenatal/Maternal record is attached
→ Prenatal follow-ups are recorded
→ TCB schedule is given

**Child / Well Baby**

→ IHP remains
→ Well Baby record is used
→ Immunization and other child services are tracked
→ TCB/follow-up is recorded

This structure is strongly supported by the interview. The client repeatedly describes the IHP as the patient's main record while consultation and specialized records are used for repeated or condition-specific encounters. 

---

# 28. Module Mapping

Here is how I would map the interview to your system:

| Interview Topic                     | Likely System Module                             |
| ----------------------------------- | ------------------------------------------------ |
| New patient interview               | **Patient Registration / IHP**                   |
| Patient demographics                | **Patient Registration**                         |
| Record/envelope number              | **Patient Record Identification**                |
| Existing patient lookup             | **Patient Directory**                            |
| IHP Pages 1–3                       | **IHP / Health Records**                         |
| Initial physical examination        | **IHP**                                          |
| Past medical history                | **IHP / Medical History**                        |
| Surgical history                    | **IHP / Surgical History**                       |
| Family history                      | **IHP / Family History**                         |
| Lifestyle history                   | **IHP / Social History**                         |
| Immunization history                | **IHP / Immunization History**                   |
| PCB quarterly information           | **IHP / PCB**                                    |
| Diagnostic/PCB encounter section    | **IHP / Possibly minimal or unused**             |
| Repeated patient visits             | **Consultation**                                 |
| Repeated vital signs                | **Consultation / Vitals**                        |
| Pregnancy history                   | **IHP + Maternal Care**                          |
| GTPAL                               | **Maternal Care / Prenatal**                     |
| AOG                                 | **Maternal Care / Prenatal**                     |
| Prenatal vital signs                | **Maternal Care / Prenatal**                     |
| Prenatal follow-ups                 | **Maternal Care**                                |
| Prenatal TCB                        | **Maternal Care**                                |
| Well baby                           | **Well Baby Care**                               |
| Baby immunization                   | **Well Baby / Immunization**                     |
| Baby card information               | **Well Baby / Immunization History**             |
| Well baby TCB                       | **Well Baby Care**                               |
| Queue                               | **Queue Management**                             |
| First come, first served            | **Queue Rules**                                  |
| Weekly service schedule             | **Appointments / Schedule**                      |
| Friday reports                      | **Reports**                                      |
| TCL                                 | **Reports / Service Reporting**                  |
| Family planning                     | **Consultation**                                 |
| Referral to CHO1                    | **Separate paper process / likely out of scope** |
| Midwife/nurse                       | **User Roles / Staff**                           |
| Two health centers / Purok coverage | **Possibly organizational/location data**        |

---

# 29. The Most Important Findings for Your IHP Design

For me, these are the points you should pay the most attention to when revising your current system:

### **1. The IHP is the patient's main long-term record.**

It is not recreated every visit or every year. 

### **2. IHP Pages 1–3 are largely initial interview/intake information.**

These are completed when the patient is first registered. 

### **3. Consultation is the repeated visit record.**

New complaints, measurements, vital signs, and examination information are recorded there whenever the patient returns. 

### **4. Initial IHP physical examination is different from ongoing vital-sign recording.**

The IHP contains initial information, while later measurements go into consultation records. 

### **5. Prenatal and Well Baby are additional records, not replacements for the IHP.**

The pregnant patient still has an IHP, but the prenatal record becomes the more actively used specialized record. 

### **6. Immunization has two different uses.**

The IHP appears to contain general immunization history, while Well Baby contains the more detailed child immunization tracking. 

### **7. Queue is currently strictly First Come, First Served.**

The client explicitly said there is no priority system. 

### **8. TCB is particularly relevant to prenatal and well-baby follow-ups.** 

### **9. Referral is currently a separate paper process.**

The client specifically said it does not need to be encoded in the computer. 

### **10. Some fields on the paper form are not actually used.**

The client said the Diagnostic Examination/PCB Services Encounter portion is not written on. 



---

## One thing I would be careful about

There are a few parts where **your question and the client's answer don't perfectly align**. The clearest example is the "past surgical history" discussion: you asked about how to record multiple surgeries, but the client responded primarily by explaining **past medical history** and how conditions such as TB, asthma, and cancer are checked. 

So I would **not use that portion as enough evidence to redesign the surgical-history field yet**.

Likewise, the opening discussion about PCB Page 3 is partially cut off, so I would treat the exact meaning of that first exchange as incomplete rather than guessing what the missing statement was. 

Overall, though, the interview gives a pretty clear picture: **IHP = patient's main baseline/long-term information, Consultation = repeated visits, Prenatal = specialized pregnancy record, Well Baby = specialized child record, Queue = first-come-first-served, and some paper processes such as referral remain outside the digital system.**

This also gives us a good basis for checking whether your **current IHP, Clinical Care Workstation, Consultation, Maternal Care, and Well Baby modules actually match the client's real workflow**, rather than just matching the paper forms.


