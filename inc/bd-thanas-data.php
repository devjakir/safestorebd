<?php
/**
 * Thana / upazila lists per Bangladesh district, keyed by the WooCommerce
 * state code (BD-xx) that the checkout District field submits.
 *
 * Generated from the bangladesh-geocode upazila list (spellings normalised to
 * current official / courier usage), plus the metropolitan police-station
 * thanas that city addresses use: Dhaka, Chattogram, Gazipur (incl. Tongi),
 * Narayanganj, Khulna and Rajshahi.
 *
 * Filter `safestorebd_bd_thanas` to add or rename entries.
 *
 * @package safestore-minimal
 */

defined( 'ABSPATH' ) || exit;

return apply_filters(
	'safestorebd_bd_thanas',
	array(
		'BD-05' => array( 'Bagerhat Sadar', 'Chitalmari', 'Fakirhat', 'Kachua', 'Mollahat', 'Mongla', 'Morrelganj', 'Rampal', 'Sarankhola' ), // Bagerhat
		'BD-01' => array( 'Alikadam', 'Bandarban Sadar', 'Lama', 'Naikhongchhari', 'Rowangchhari', 'Ruma', 'Thanchi' ), // Bandarban
		'BD-02' => array( 'Amtali', 'Bamna', 'Barguna Sadar', 'Betagi', 'Pathorghata', 'Taltali' ), // Barguna
		'BD-06' => array( 'Agailjhara', 'Babuganj', 'Bakerganj', 'Banaripara', 'Barishal Sadar', 'Gaurnadi', 'Hizla', 'Mehendiganj', 'Muladi', 'Wazirpur' ), // Barishal
		'BD-07' => array( 'Bhola Sadar', 'Burhanuddin', 'Char Fasson', 'Daulatkhan', 'Lalmohan', 'Monpura', 'Tazumuddin' ), // Bhola
		'BD-03' => array( 'Adamdighi', 'Bogura Sadar', 'Dhunat', 'Dupchanchia', 'Gabtali', 'Kahaloo', 'Nandigram', 'Shajahanpur', 'Shariakandi', 'Sherpur', 'Shibganj', 'Sonatala' ), // Bogura
		'BD-04' => array( 'Akhaura', 'Ashuganj', 'Bancharampur', 'Bijoynagar', 'Brahmanbaria Sadar', 'Kasba', 'Nabinagar', 'Nasirnagar', 'Sarail' ), // Brahmanbaria
		'BD-09' => array( 'Chandpur Sadar', 'Faridganj', 'Haimchar', 'Hajiganj', 'Kachua', 'Matlab North', 'Matlab South', 'Shahrasti' ), // Chandpur
		'BD-10' => array( 'Akbar Shah', 'Anwara', 'Bakalia', 'Bandar', 'Banshkhali', 'Bayazid Bostami', 'Boalkhali', 'Chandanaish', 'Chandgaon', 'Chawkbazar', 'Double Mooring', 'EPZ', 'Fatikchhari', 'Halishahar', 'Hathazari', 'Karnaphuli', 'Khulshi', 'Kotwali', 'Lohagara', 'Mirsharai', 'Pahartali', 'Panchlaish', 'Patenga', 'Patiya', 'Rangunia', 'Raozan', 'Sadarghat', 'Sandwip', 'Satkania', 'Sitakunda' ), // Chattogram
		'BD-12' => array( 'Alamdanga', 'Chuadanga Sadar', 'Damurhuda', 'Jibannagar' ), // Chuadanga
		'BD-11' => array( 'Chakaria', 'Cox\'s Bazar Sadar', 'Eidgaon', 'Kutubdia', 'Maheshkhali', 'Pekua', 'Ramu', 'Teknaf', 'Ukhiya' ), // Cox's Bazar
		'BD-08' => array( 'Barura', 'Brahmanpara', 'Burichang', 'Chandina', 'Chauddagram', 'Cumilla Adarsha Sadar', 'Cumilla Sadar Dakshin', 'Daudkandi', 'Debidwar', 'Homna', 'Laksam', 'Lalmai', 'Manoharganj', 'Meghna', 'Muradnagar', 'Nangalkot', 'Titas' ), // Cumilla
		'BD-13' => array( 'Adabor', 'Ashulia', 'Badda', 'Banani', 'Bangshal', 'Bhashantek', 'Bhatara', 'Bimanbandar (Airport)', 'Cantonment', 'Chawkbazar', 'Dakshinkhan', 'Darus Salam', 'Demra', 'Dhamrai', 'Dhanmondi', 'Dohar', 'Gendaria', 'Gulshan', 'Hatirjheel', 'Hazaribagh', 'Jatrabari', 'Kadamtali', 'Kafrul', 'Kalabagan', 'Kamrangirchar', 'Keraniganj', 'Khilgaon', 'Khilkhet', 'Kotwali', 'Lalbagh', 'Mirpur', 'Mohammadpur', 'Motijheel', 'Mugda', 'Nawabganj', 'New Market', 'Pallabi', 'Paltan', 'Ramna', 'Rampura', 'Rupnagar', 'Sabujbagh', 'Savar', 'Shah Ali', 'Shahbagh', 'Shahjahanpur', 'Sher-e-Bangla Nagar', 'Shyampur', 'Sutrapur', 'Tejgaon', 'Tejgaon Industrial Area', 'Turag', 'Uttara East', 'Uttara West', 'Uttarkhan', 'Wari' ), // Dhaka
		'BD-14' => array( 'Biral', 'Birampur', 'Birganj', 'Bochaganj', 'Chirirbandar', 'Dinajpur Sadar', 'Ghoraghat', 'Hakimpur', 'Kaharol', 'Khansama', 'Nawabganj', 'Parbatipur', 'Phulbari' ), // Dinajpur
		'BD-15' => array( 'Alfadanga', 'Bhanga', 'Boalmari', 'Char Bhadrasan', 'Faridpur Sadar', 'Madhukhali', 'Nagarkanda', 'Sadarpur', 'Saltha' ), // Faridpur
		'BD-16' => array( 'Chhagalnaiya', 'Daganbhuiyan', 'Feni Sadar', 'Fulgazi', 'Parshuram', 'Sonagazi' ), // Feni
		'BD-19' => array( 'Gaibandha Sadar', 'Gobindaganj', 'Palashbari', 'Phulchari', 'Sadullapur', 'Saghata', 'Sundarganj' ), // Gaibandha
		'BD-18' => array( 'Basan', 'Gacha', 'Gazipur Sadar', 'Kaliakair', 'Kaliganj', 'Kapasia', 'Kashimpur', 'Konabari', 'Pubail', 'Sreepur', 'Tongi East', 'Tongi West' ), // Gazipur
		'BD-17' => array( 'Gopalganj Sadar', 'Kashiani', 'Kotalipara', 'Muksudpur', 'Tungipara' ), // Gopalganj
		'BD-20' => array( 'Ajmiriganj', 'Bahubal', 'Baniachong', 'Chunarughat', 'Habiganj Sadar', 'Lakhai', 'Madhabpur', 'Nabiganj', 'Shayestaganj' ), // Habiganj
		'BD-21' => array( 'Bakshiganj', 'Dewanganj', 'Islampur', 'Jamalpur Sadar', 'Madarganj', 'Melandah', 'Sarishabari' ), // Jamalpur
		'BD-22' => array( 'Abhaynagar', 'Bagherpara', 'Chougachha', 'Jashore Sadar', 'Jhikargachha', 'Keshabpur', 'Manirampur', 'Sharsha' ), // Jashore
		'BD-25' => array( 'Jhalokati Sadar', 'Kathalia', 'Nalchity', 'Rajapur' ), // Jhalokati
		'BD-23' => array( 'Harinakundu', 'Jhenaidah Sadar', 'Kaliganj', 'Kotchandpur', 'Moheshpur', 'Shailkupa' ), // Jhenaidah
		'BD-24' => array( 'Akkelpur', 'Joypurhat Sadar', 'Kalai', 'Khetlal', 'Panchbibi' ), // Joypurhat
		'BD-29' => array( 'Dighinala', 'Guimara', 'Khagrachhari Sadar', 'Laxmichhari', 'Manikchari', 'Matiranga', 'Mohalchari', 'Panchari', 'Ramgarh' ), // Khagrachhari
		'BD-27' => array( 'Batiaghata', 'Dakop', 'Daulatpur', 'Dighalia', 'Dumuria', 'Khalishpur', 'Khan Jahan Ali', 'Khulna Sadar', 'Koyra', 'Paikgachha', 'Phultala', 'Rupsha', 'Sonadanga', 'Terokhada' ), // Khulna
		'BD-26' => array( 'Austagram', 'Bajitpur', 'Bhairab', 'Hossainpur', 'Itna', 'Karimganj', 'Katiadi', 'Kishoreganj Sadar', 'Kuliarchar', 'Mithamain', 'Nikli', 'Pakundia', 'Tarail' ), // Kishoreganj
		'BD-28' => array( 'Bhurungamari', 'Char Rajibpur', 'Chilmari', 'Kurigram Sadar', 'Nageshwari', 'Phulbari', 'Rajarhat', 'Rowmari', 'Ulipur' ), // Kurigram
		'BD-30' => array( 'Bheramara', 'Daulatpur', 'Khoksa', 'Kumarkhali', 'Kushtia Sadar', 'Mirpur' ), // Kushtia
		'BD-31' => array( 'Kamalnagar', 'Lakshmipur Sadar', 'Raipur', 'Ramganj', 'Ramgati' ), // Lakshmipur
		'BD-32' => array( 'Aditmari', 'Hatibandha', 'Kaliganj', 'Lalmonirhat Sadar', 'Patgram' ), // Lalmonirhat
		'BD-36' => array( 'Dasar', 'Kalkini', 'Madaripur Sadar', 'Rajoir', 'Shibchar' ), // Madaripur
		'BD-37' => array( 'Magura Sadar', 'Mohammadpur', 'Shalikha', 'Sreepur' ), // Magura
		'BD-33' => array( 'Daulatpur', 'Ghior', 'Harirampur', 'Manikganj Sadar', 'Saturia', 'Shivalaya', 'Singair' ), // Manikganj
		'BD-39' => array( 'Gangni', 'Meherpur Sadar', 'Mujibnagar' ), // Meherpur
		'BD-38' => array( 'Barlekha', 'Juri', 'Kamalganj', 'Kulaura', 'Moulvibazar Sadar', 'Rajnagar', 'Sreemangal' ), // Moulvibazar
		'BD-35' => array( 'Gajaria', 'Louhajang', 'Munshiganj Sadar', 'Sirajdikhan', 'Sreenagar', 'Tongibari' ), // Munshiganj
		'BD-34' => array( 'Bhaluka', 'Dhobaura', 'Fulbaria', 'Gafargaon', 'Gauripur', 'Haluaghat', 'Ishwarganj', 'Muktagacha', 'Mymensingh Sadar', 'Nandail', 'Phulpur', 'Tarakanda', 'Trishal' ), // Mymensingh
		'BD-48' => array( 'Atrai', 'Badalgachi', 'Dhamoirhat', 'Mahadebpur', 'Manda', 'Naogaon Sadar', 'Niamatpur', 'Patnitala', 'Porsha', 'Raninagar', 'Sapahar' ), // Naogaon
		'BD-43' => array( 'Kalia', 'Lohagara', 'Narail Sadar' ), // Narail
		'BD-40' => array( 'Araihazar', 'Bandar', 'Fatullah', 'Narayanganj Sadar', 'Rupganj', 'Siddhirganj', 'Sonargaon' ), // Narayanganj
		'BD-42' => array( 'Belabo', 'Monohardi', 'Narsingdi Sadar', 'Palash', 'Raipura', 'Shibpur' ), // Narsingdi
		'BD-44' => array( 'Bagatipara', 'Baraigram', 'Gurudaspur', 'Lalpur', 'Naldanga', 'Natore Sadar', 'Singra' ), // Natore
		'BD-45' => array( 'Bholahat', 'Chapainawabganj Sadar', 'Gomostapur', 'Nachol', 'Shibganj' ), // Nawabganj
		'BD-41' => array( 'Atpara', 'Barhatta', 'Durgapur', 'Kalmakanda', 'Kendua', 'Khaliajuri', 'Madan', 'Mohanganj', 'Netrakona Sadar', 'Purbadhala' ), // Netrakona
		'BD-46' => array( 'Dimla', 'Domar', 'Jaldhaka', 'Kishorganj', 'Nilphamari Sadar', 'Saidpur' ), // Nilphamari
		'BD-47' => array( 'Begumganj', 'Chatkhil', 'Companiganj', 'Hatia', 'Kabirhat', 'Noakhali Sadar', 'Senbagh', 'Sonaimuri', 'Subarnachar' ), // Noakhali
		'BD-49' => array( 'Atgharia', 'Bera', 'Bhangura', 'Chatmohar', 'Faridpur', 'Ishurdi', 'Pabna Sadar', 'Santhia', 'Sujanagar' ), // Pabna
		'BD-52' => array( 'Atwari', 'Boda', 'Debiganj', 'Panchagarh Sadar', 'Tetulia' ), // Panchagarh
		'BD-51' => array( 'Bauphal', 'Dashmina', 'Dumki', 'Galachipa', 'Kalapara', 'Mirzaganj', 'Patuakhali Sadar', 'Rangabali' ), // Patuakhali
		'BD-50' => array( 'Bhandaria', 'Indurkani', 'Kawkhali', 'Mathbaria', 'Nazirpur', 'Nesarabad (Swarupkathi)', 'Pirojpur Sadar' ), // Pirojpur
		'BD-53' => array( 'Baliakandi', 'Goalanda', 'Kalukhali', 'Pangsa', 'Rajbari Sadar' ), // Rajbari
		'BD-54' => array( 'Bagha', 'Bagmara', 'Boalia', 'Charghat', 'Durgapur', 'Godagari', 'Mohanpur', 'Motihar', 'Paba', 'Puthia', 'Rajpara', 'Shah Makhdum', 'Tanore' ), // Rajshahi
		'BD-56' => array( 'Baghaichari', 'Barkal', 'Belaichari', 'Juraichari', 'Kaptai', 'Kawkhali', 'Langadu', 'Naniarchar', 'Rajasthali', 'Rangamati Sadar' ), // Rangamati
		'BD-55' => array( 'Badarganj', 'Gangachara', 'Kaunia', 'Mithapukur', 'Pirgacha', 'Pirganj', 'Rangpur Sadar', 'Taraganj' ), // Rangpur
		'BD-58' => array( 'Assasuni', 'Debhata', 'Kalaroa', 'Kaliganj', 'Satkhira Sadar', 'Shyamnagar', 'Tala' ), // Satkhira
		'BD-62' => array( 'Bhedarganj', 'Damudya', 'Gosairhat', 'Naria', 'Shariatpur Sadar', 'Zanjira' ), // Shariatpur
		'BD-57' => array( 'Jhenaigati', 'Nakla', 'Nalitabari', 'Sherpur Sadar', 'Sreebardi' ), // Sherpur
		'BD-59' => array( 'Belkuchi', 'Chauhali', 'Kamarkhand', 'Kazipur', 'Raiganj', 'Shahjadpur', 'Sirajganj Sadar', 'Tarash', 'Ullapara' ), // Sirajganj
		'BD-61' => array( 'Bishwambarpur', 'Chhatak', 'Derai', 'Dharmapasha', 'Dowarabazar', 'Jagannathpur', 'Jamalganj', 'Madhyanagar', 'Shalla', 'Shantiganj', 'Sunamganj Sadar', 'Tahirpur' ), // Sunamganj
		'BD-60' => array( 'Balaganj', 'Beanibazar', 'Bishwanath', 'Companiganj', 'Dakshin Surma', 'Fenchuganj', 'Golapganj', 'Gowainghat', 'Jaintiapur', 'Kanaighat', 'Osmaninagar', 'Sylhet Sadar', 'Zakiganj' ), // Sylhet
		'BD-63' => array( 'Basail', 'Bhuapur', 'Delduar', 'Dhanbari', 'Ghatail', 'Gopalpur', 'Kalihati', 'Madhupur', 'Mirzapur', 'Nagarpur', 'Sakhipur', 'Tangail Sadar' ), // Tangail
		'BD-64' => array( 'Baliadangi', 'Haripur', 'Pirganj', 'Ranisankail', 'Thakurgaon Sadar' ), // Thakurgaon
	)
);
