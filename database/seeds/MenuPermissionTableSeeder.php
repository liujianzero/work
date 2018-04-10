<?php

use Illuminate\Database\Seeder;

class MenuPermissionTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('menu_permission')->delete();
        
        \DB::table('menu_permission')->insert(array (
            0 => 
            array (
                'id' => 5,
                'menu_id' => 1,
                'permission_id' => 93,
            ),
            1 => 
            array (
                'id' => 9,
                'menu_id' => 68,
                'permission_id' => 103,
            ),
            2 => 
            array (
                'id' => 10,
                'menu_id' => 68,
                'permission_id' => 104,
            ),
            3 => 
            array (
                'id' => 11,
                'menu_id' => 68,
                'permission_id' => 105,
            ),
            4 => 
            array (
                'id' => 12,
                'menu_id' => 68,
                'permission_id' => 106,
            ),
            5 => 
            array (
                'id' => 13,
                'menu_id' => 68,
                'permission_id' => 107,
            ),
            6 => 
            array (
                'id' => 17,
                'menu_id' => 71,
                'permission_id' => 111,
            ),
            7 => 
            array (
                'id' => 19,
                'menu_id' => 90,
                'permission_id' => 113,
            ),
            8 => 
            array (
                'id' => 20,
                'menu_id' => 90,
                'permission_id' => 114,
            ),
            9 => 
            array (
                'id' => 21,
                'menu_id' => 90,
                'permission_id' => 115,
            ),
            10 => 
            array (
                'id' => 22,
                'menu_id' => 90,
                'permission_id' => 116,
            ),
            11 => 
            array (
                'id' => 23,
                'menu_id' => 90,
                'permission_id' => 117,
            ),
            12 => 
            array (
                'id' => 24,
                'menu_id' => 90,
                'permission_id' => 118,
            ),
            13 => 
            array (
                'id' => 26,
                'menu_id' => 91,
                'permission_id' => 121,
            ),
            14 => 
            array (
                'id' => 27,
                'menu_id' => 91,
                'permission_id' => 122,
            ),
            15 => 
            array (
                'id' => 28,
                'menu_id' => 91,
                'permission_id' => 123,
            ),
            16 => 
            array (
                'id' => 29,
                'menu_id' => 91,
                'permission_id' => 124,
            ),
            17 => 
            array (
                'id' => 30,
                'menu_id' => 91,
                'permission_id' => 125,
            ),
            18 => 
            array (
                'id' => 32,
                'menu_id' => 93,
                'permission_id' => 127,
            ),
            19 => 
            array (
                'id' => 37,
                'menu_id' => 91,
                'permission_id' => 120,
            ),
            20 => 
            array (
                'id' => 39,
                'menu_id' => 40,
                'permission_id' => 135,
            ),
            21 => 
            array (
                'id' => 42,
                'menu_id' => 56,
                'permission_id' => 138,
            ),
            22 => 
            array (
                'id' => 44,
                'menu_id' => 56,
                'permission_id' => 140,
            ),
            23 => 
            array (
                'id' => 47,
                'menu_id' => 57,
                'permission_id' => 143,
            ),
            24 => 
            array (
                'id' => 49,
                'menu_id' => 57,
                'permission_id' => 145,
            ),
            25 => 
            array (
                'id' => 51,
                'menu_id' => 75,
                'permission_id' => 147,
            ),
            26 => 
            array (
                'id' => 52,
                'menu_id' => 75,
                'permission_id' => 148,
            ),
            27 => 
            array (
                'id' => 55,
                'menu_id' => 75,
                'permission_id' => 151,
            ),
            28 => 
            array (
                'id' => 56,
                'menu_id' => 75,
                'permission_id' => 152,
            ),
            29 => 
            array (
                'id' => 57,
                'menu_id' => 58,
                'permission_id' => 153,
            ),
            30 => 
            array (
                'id' => 58,
                'menu_id' => 58,
                'permission_id' => 154,
            ),
            31 => 
            array (
                'id' => 59,
                'menu_id' => 58,
                'permission_id' => 155,
            ),
            32 => 
            array (
                'id' => 60,
                'menu_id' => 58,
                'permission_id' => 156,
            ),
            33 => 
            array (
                'id' => 61,
                'menu_id' => 58,
                'permission_id' => 157,
            ),
            34 => 
            array (
                'id' => 62,
                'menu_id' => 45,
                'permission_id' => 158,
            ),
            35 => 
            array (
                'id' => 63,
                'menu_id' => 45,
                'permission_id' => 159,
            ),
            36 => 
            array (
                'id' => 64,
                'menu_id' => 45,
                'permission_id' => 160,
            ),
            37 => 
            array (
                'id' => 65,
                'menu_id' => 45,
                'permission_id' => 161,
            ),
            38 => 
            array (
                'id' => 66,
                'menu_id' => 45,
                'permission_id' => 162,
            ),
            39 => 
            array (
                'id' => 68,
                'menu_id' => 105,
                'permission_id' => 164,
            ),
            40 => 
            array (
                'id' => 69,
                'menu_id' => 105,
                'permission_id' => 165,
            ),
            41 => 
            array (
                'id' => 70,
                'menu_id' => 105,
                'permission_id' => 166,
            ),
            42 => 
            array (
                'id' => 72,
                'menu_id' => 81,
                'permission_id' => 168,
            ),
            43 => 
            array (
                'id' => 73,
                'menu_id' => 81,
                'permission_id' => 169,
            ),
            44 => 
            array (
                'id' => 74,
                'menu_id' => 81,
                'permission_id' => 170,
            ),
            45 => 
            array (
                'id' => 77,
                'menu_id' => 46,
                'permission_id' => 173,
            ),
            46 => 
            array (
                'id' => 78,
                'menu_id' => 46,
                'permission_id' => 174,
            ),
            47 => 
            array (
                'id' => 79,
                'menu_id' => 46,
                'permission_id' => 175,
            ),
            48 => 
            array (
                'id' => 80,
                'menu_id' => 46,
                'permission_id' => 176,
            ),
            49 => 
            array (
                'id' => 81,
                'menu_id' => 46,
                'permission_id' => 177,
            ),
            50 => 
            array (
                'id' => 82,
                'menu_id' => 46,
                'permission_id' => 178,
            ),
            51 => 
            array (
                'id' => 83,
                'menu_id' => 61,
                'permission_id' => 179,
            ),
            52 => 
            array (
                'id' => 84,
                'menu_id' => 61,
                'permission_id' => 180,
            ),
            53 => 
            array (
                'id' => 85,
                'menu_id' => 61,
                'permission_id' => 181,
            ),
            54 => 
            array (
                'id' => 86,
                'menu_id' => 61,
                'permission_id' => 182,
            ),
            55 => 
            array (
                'id' => 87,
                'menu_id' => 61,
                'permission_id' => 183,
            ),
            56 => 
            array (
                'id' => 88,
                'menu_id' => 61,
                'permission_id' => 184,
            ),
            57 => 
            array (
                'id' => 89,
                'menu_id' => 61,
                'permission_id' => 185,
            ),
            58 => 
            array (
                'id' => 90,
                'menu_id' => 61,
                'permission_id' => 186,
            ),
            59 => 
            array (
                'id' => 91,
                'menu_id' => 60,
                'permission_id' => 187,
            ),
            60 => 
            array (
                'id' => 92,
                'menu_id' => 60,
                'permission_id' => 188,
            ),
            61 => 
            array (
                'id' => 93,
                'menu_id' => 60,
                'permission_id' => 189,
            ),
            62 => 
            array (
                'id' => 94,
                'menu_id' => 60,
                'permission_id' => 190,
            ),
            63 => 
            array (
                'id' => 95,
                'menu_id' => 60,
                'permission_id' => 191,
            ),
            64 => 
            array (
                'id' => 96,
                'menu_id' => 60,
                'permission_id' => 192,
            ),
            65 => 
            array (
                'id' => 97,
                'menu_id' => 60,
                'permission_id' => 193,
            ),
            66 => 
            array (
                'id' => 98,
                'menu_id' => 60,
                'permission_id' => 194,
            ),
            67 => 
            array (
                'id' => 99,
                'menu_id' => 60,
                'permission_id' => 195,
            ),
            68 => 
            array (
                'id' => 107,
                'menu_id' => 52,
                'permission_id' => 203,
            ),
            69 => 
            array (
                'id' => 108,
                'menu_id' => 52,
                'permission_id' => 204,
            ),
            70 => 
            array (
                'id' => 109,
                'menu_id' => 52,
                'permission_id' => 205,
            ),
            71 => 
            array (
                'id' => 111,
                'menu_id' => 54,
                'permission_id' => 206,
            ),
            72 => 
            array (
                'id' => 112,
                'menu_id' => 54,
                'permission_id' => 207,
            ),
            73 => 
            array (
                'id' => 113,
                'menu_id' => 54,
                'permission_id' => 208,
            ),
            74 => 
            array (
                'id' => 114,
                'menu_id' => 54,
                'permission_id' => 209,
            ),
            75 => 
            array (
                'id' => 115,
                'menu_id' => 54,
                'permission_id' => 210,
            ),
            76 => 
            array (
                'id' => 116,
                'menu_id' => 53,
                'permission_id' => 211,
            ),
            77 => 
            array (
                'id' => 117,
                'menu_id' => 53,
                'permission_id' => 212,
            ),
            78 => 
            array (
                'id' => 118,
                'menu_id' => 53,
                'permission_id' => 213,
            ),
            79 => 
            array (
                'id' => 119,
                'menu_id' => 53,
                'permission_id' => 214,
            ),
            80 => 
            array (
                'id' => 120,
                'menu_id' => 53,
                'permission_id' => 215,
            ),
            81 => 
            array (
                'id' => 121,
                'menu_id' => 67,
                'permission_id' => 216,
            ),
            82 => 
            array (
                'id' => 122,
                'menu_id' => 67,
                'permission_id' => 217,
            ),
            83 => 
            array (
                'id' => 123,
                'menu_id' => 67,
                'permission_id' => 218,
            ),
            84 => 
            array (
                'id' => 124,
                'menu_id' => 67,
                'permission_id' => 219,
            ),
            85 => 
            array (
                'id' => 125,
                'menu_id' => 67,
                'permission_id' => 220,
            ),
            86 => 
            array (
                'id' => 126,
                'menu_id' => 67,
                'permission_id' => 221,
            ),
            87 => 
            array (
                'id' => 130,
                'menu_id' => 96,
                'permission_id' => 225,
            ),
            88 => 
            array (
                'id' => 131,
                'menu_id' => 97,
                'permission_id' => 226,
            ),
            89 => 
            array (
                'id' => 132,
                'menu_id' => 97,
                'permission_id' => 227,
            ),
            90 => 
            array (
                'id' => 133,
                'menu_id' => 98,
                'permission_id' => 228,
            ),
            91 => 
            array (
                'id' => 134,
                'menu_id' => 98,
                'permission_id' => 229,
            ),
            92 => 
            array (
                'id' => 135,
                'menu_id' => 99,
                'permission_id' => 230,
            ),
            93 => 
            array (
                'id' => 138,
                'menu_id' => 103,
                'permission_id' => 233,
            ),
            94 => 
            array (
                'id' => 139,
                'menu_id' => 98,
                'permission_id' => 234,
            ),
            95 => 
            array (
                'id' => 140,
                'menu_id' => 97,
                'permission_id' => 235,
            ),
            96 => 
            array (
                'id' => 141,
                'menu_id' => 97,
                'permission_id' => 236,
            ),
            97 => 
            array (
                'id' => 143,
                'menu_id' => 97,
                'permission_id' => 238,
            ),
            98 => 
            array (
                'id' => 144,
                'menu_id' => 38,
                'permission_id' => 239,
            ),
            99 => 
            array (
                'id' => 145,
                'menu_id' => 38,
                'permission_id' => 240,
            ),
            100 => 
            array (
                'id' => 146,
                'menu_id' => 38,
                'permission_id' => 241,
            ),
            101 => 
            array (
                'id' => 147,
                'menu_id' => 38,
                'permission_id' => 242,
            ),
            102 => 
            array (
                'id' => 148,
                'menu_id' => 38,
                'permission_id' => 243,
            ),
            103 => 
            array (
                'id' => 149,
                'menu_id' => 40,
                'permission_id' => 244,
            ),
            104 => 
            array (
                'id' => 150,
                'menu_id' => 40,
                'permission_id' => 245,
            ),
            105 => 
            array (
                'id' => 151,
                'menu_id' => 39,
                'permission_id' => 246,
            ),
            106 => 
            array (
                'id' => 152,
                'menu_id' => 39,
                'permission_id' => 247,
            ),
            107 => 
            array (
                'id' => 153,
                'menu_id' => 43,
                'permission_id' => 248,
            ),
            108 => 
            array (
                'id' => 154,
                'menu_id' => 43,
                'permission_id' => 249,
            ),
            109 => 
            array (
                'id' => 155,
                'menu_id' => 43,
                'permission_id' => 250,
            ),
            110 => 
            array (
                'id' => 156,
                'menu_id' => 43,
                'permission_id' => 251,
            ),
            111 => 
            array (
                'id' => 157,
                'menu_id' => 43,
                'permission_id' => 252,
            ),
            112 => 
            array (
                'id' => 158,
                'menu_id' => 43,
                'permission_id' => 253,
            ),
            113 => 
            array (
                'id' => 159,
                'menu_id' => 44,
                'permission_id' => 254,
            ),
            114 => 
            array (
                'id' => 160,
                'menu_id' => 44,
                'permission_id' => 255,
            ),
            115 => 
            array (
                'id' => 162,
                'menu_id' => 83,
                'permission_id' => 257,
            ),
            116 => 
            array (
                'id' => 184,
                'menu_id' => 106,
                'permission_id' => 99,
            ),
            117 => 
            array (
                'id' => 197,
                'menu_id' => 68,
                'permission_id' => 292,
            ),
            118 => 
            array (
                'id' => 199,
                'menu_id' => 68,
                'permission_id' => 294,
            ),
            119 => 
            array (
                'id' => 207,
                'menu_id' => 68,
                'permission_id' => 298,
            ),
            120 => 
            array (
                'id' => 209,
                'menu_id' => 85,
                'permission_id' => 300,
            ),
            121 => 
            array (
                'id' => 211,
                'menu_id' => 85,
                'permission_id' => 301,
            ),
            122 => 
            array (
                'id' => 214,
                'menu_id' => 1,
                'permission_id' => 303,
            ),
            123 => 
            array (
                'id' => 216,
                'menu_id' => 89,
                'permission_id' => 304,
            ),
            124 => 
            array (
                'id' => 217,
                'menu_id' => 89,
                'permission_id' => 305,
            ),
            125 => 
            array (
                'id' => 218,
                'menu_id' => 70,
                'permission_id' => 109,
            ),
            126 => 
            array (
                'id' => 219,
                'menu_id' => 70,
                'permission_id' => 108,
            ),
            127 => 
            array (
                'id' => 221,
                'menu_id' => 38,
                'permission_id' => 280,
            ),
            128 => 
            array (
                'id' => 222,
                'menu_id' => 38,
                'permission_id' => 281,
            ),
            129 => 
            array (
                'id' => 224,
                'menu_id' => 107,
                'permission_id' => 306,
            ),
            130 => 
            array (
                'id' => 231,
                'menu_id' => 113,
                'permission_id' => 310,
            ),
            131 => 
            array (
                'id' => 232,
                'menu_id' => 113,
                'permission_id' => 311,
            ),
            132 => 
            array (
                'id' => 233,
                'menu_id' => 113,
                'permission_id' => 312,
            ),
            133 => 
            array (
                'id' => 234,
                'menu_id' => 113,
                'permission_id' => 313,
            ),
            134 => 
            array (
                'id' => 237,
                'menu_id' => 114,
                'permission_id' => 316,
            ),
            135 => 
            array (
                'id' => 246,
                'menu_id' => 120,
                'permission_id' => 319,
            ),
            136 => 
            array (
                'id' => 247,
                'menu_id' => 120,
                'permission_id' => 320,
            ),
            137 => 
            array (
                'id' => 250,
                'menu_id' => 121,
                'permission_id' => 327,
            ),
            138 => 
            array (
                'id' => 252,
                'menu_id' => 126,
                'permission_id' => 329,
            ),
            139 => 
            array (
                'id' => 253,
                'menu_id' => 126,
                'permission_id' => 330,
            ),
            140 => 
            array (
                'id' => 254,
                'menu_id' => 126,
                'permission_id' => 331,
            ),
            141 => 
            array (
                'id' => 255,
                'menu_id' => 126,
                'permission_id' => 332,
            ),
            142 => 
            array (
                'id' => 256,
                'menu_id' => 126,
                'permission_id' => 333,
            ),
            143 => 
            array (
                'id' => 258,
                'menu_id' => 125,
                'permission_id' => 335,
            ),
            144 => 
            array (
                'id' => 259,
                'menu_id' => 125,
                'permission_id' => 336,
            ),
            145 => 
            array (
                'id' => 261,
                'menu_id' => 127,
                'permission_id' => 338,
            ),
            146 => 
            array (
                'id' => 263,
                'menu_id' => 70,
                'permission_id' => 110,
            ),
            147 => 
            array (
                'id' => 265,
                'menu_id' => 1,
                'permission_id' => 341,
            ),
            148 => 
            array (
                'id' => 266,
                'menu_id' => 104,
                'permission_id' => 342,
            ),
            149 => 
            array (
                'id' => 267,
                'menu_id' => 1,
                'permission_id' => 343,
            ),
            150 => 
            array (
                'id' => 277,
                'menu_id' => 133,
                'permission_id' => 285,
            ),
            151 => 
            array (
                'id' => 278,
                'menu_id' => 132,
                'permission_id' => 129,
            ),
            152 => 
            array (
                'id' => 279,
                'menu_id' => 134,
                'permission_id' => 276,
            ),
            153 => 
            array (
                'id' => 280,
                'menu_id' => 136,
                'permission_id' => 258,
            ),
            154 => 
            array (
                'id' => 281,
                'menu_id' => 137,
                'permission_id' => 266,
            ),
            155 => 
            array (
                'id' => 282,
                'menu_id' => 136,
                'permission_id' => 260,
            ),
            156 => 
            array (
                'id' => 283,
                'menu_id' => 136,
                'permission_id' => 261,
            ),
            157 => 
            array (
                'id' => 284,
                'menu_id' => 136,
                'permission_id' => 263,
            ),
            158 => 
            array (
                'id' => 285,
                'menu_id' => 136,
                'permission_id' => 259,
            ),
            159 => 
            array (
                'id' => 286,
                'menu_id' => 137,
                'permission_id' => 267,
            ),
            160 => 
            array (
                'id' => 287,
                'menu_id' => 137,
                'permission_id' => 268,
            ),
            161 => 
            array (
                'id' => 288,
                'menu_id' => 137,
                'permission_id' => 269,
            ),
            162 => 
            array (
                'id' => 289,
                'menu_id' => 137,
                'permission_id' => 270,
            ),
            163 => 
            array (
                'id' => 290,
                'menu_id' => 136,
                'permission_id' => 265,
            ),
            164 => 
            array (
                'id' => 291,
                'menu_id' => 137,
                'permission_id' => 271,
            ),
            165 => 
            array (
                'id' => 292,
                'menu_id' => 137,
                'permission_id' => 273,
            ),
            166 => 
            array (
                'id' => 293,
                'menu_id' => 137,
                'permission_id' => 275,
            ),
            167 => 
            array (
                'id' => 294,
                'menu_id' => 132,
                'permission_id' => 130,
            ),
            168 => 
            array (
                'id' => 297,
                'menu_id' => 133,
                'permission_id' => 286,
            ),
            169 => 
            array (
                'id' => 298,
                'menu_id' => 133,
                'permission_id' => 293,
            ),
            170 => 
            array (
                'id' => 299,
                'menu_id' => 140,
                'permission_id' => 282,
            ),
            171 => 
            array (
                'id' => 301,
                'menu_id' => 140,
                'permission_id' => 278,
            ),
            172 => 
            array (
                'id' => 302,
                'menu_id' => 140,
                'permission_id' => 277,
            ),
            173 => 
            array (
                'id' => 303,
                'menu_id' => 144,
                'permission_id' => 283,
            ),
            174 => 
            array (
                'id' => 304,
                'menu_id' => 144,
                'permission_id' => 284,
            ),
            175 => 
            array (
                'id' => 305,
                'menu_id' => 144,
                'permission_id' => 297,
            ),
            176 => 
            array (
                'id' => 306,
                'menu_id' => 138,
                'permission_id' => 196,
            ),
            177 => 
            array (
                'id' => 307,
                'menu_id' => 138,
                'permission_id' => 198,
            ),
            178 => 
            array (
                'id' => 308,
                'menu_id' => 138,
                'permission_id' => 199,
            ),
            179 => 
            array (
                'id' => 309,
                'menu_id' => 138,
                'permission_id' => 200,
            ),
            180 => 
            array (
                'id' => 310,
                'menu_id' => 138,
                'permission_id' => 201,
            ),
            181 => 
            array (
                'id' => 311,
                'menu_id' => 138,
                'permission_id' => 197,
            ),
            182 => 
            array (
                'id' => 312,
                'menu_id' => 138,
                'permission_id' => 202,
            ),
            183 => 
            array (
                'id' => 313,
                'menu_id' => 149,
                'permission_id' => 171,
            ),
            184 => 
            array (
                'id' => 314,
                'menu_id' => 149,
                'permission_id' => 172,
            ),
            185 => 
            array (
                'id' => 315,
                'menu_id' => 151,
                'permission_id' => 136,
            ),
            186 => 
            array (
                'id' => 316,
                'menu_id' => 151,
                'permission_id' => 137,
            ),
            187 => 
            array (
                'id' => 317,
                'menu_id' => 151,
                'permission_id' => 139,
            ),
            188 => 
            array (
                'id' => 318,
                'menu_id' => 152,
                'permission_id' => 141,
            ),
            189 => 
            array (
                'id' => 319,
                'menu_id' => 152,
                'permission_id' => 142,
            ),
            190 => 
            array (
                'id' => 320,
                'menu_id' => 152,
                'permission_id' => 144,
            ),
            191 => 
            array (
                'id' => 321,
                'menu_id' => 142,
                'permission_id' => 314,
            ),
            192 => 
            array (
                'id' => 322,
                'menu_id' => 143,
                'permission_id' => 317,
            ),
            193 => 
            array (
                'id' => 325,
                'menu_id' => 125,
                'permission_id' => 334,
            ),
            194 => 
            array (
                'id' => 327,
                'menu_id' => 126,
                'permission_id' => 328,
            ),
            195 => 
            array (
                'id' => 328,
                'menu_id' => 127,
                'permission_id' => 337,
            ),
            196 => 
            array (
                'id' => 329,
                'menu_id' => 121,
                'permission_id' => 326,
            ),
            197 => 
            array (
                'id' => 330,
                'menu_id' => 155,
                'permission_id' => 237,
            ),
            198 => 
            array (
                'id' => 331,
                'menu_id' => 120,
                'permission_id' => 318,
            ),
            199 => 
            array (
                'id' => 332,
                'menu_id' => 122,
                'permission_id' => 321,
            ),
            200 => 
            array (
                'id' => 333,
                'menu_id' => 146,
                'permission_id' => 324,
            ),
            201 => 
            array (
                'id' => 334,
                'menu_id' => 156,
                'permission_id' => 232,
            ),
            202 => 
            array (
                'id' => 335,
                'menu_id' => 157,
                'permission_id' => 222,
            ),
            203 => 
            array (
                'id' => 336,
                'menu_id' => 157,
                'permission_id' => 223,
            ),
            204 => 
            array (
                'id' => 337,
                'menu_id' => 159,
                'permission_id' => 231,
            ),
            205 => 
            array (
                'id' => 338,
                'menu_id' => 159,
                'permission_id' => 224,
            ),
            206 => 
            array (
                'id' => 341,
                'menu_id' => 92,
                'permission_id' => 279,
            ),
            207 => 
            array (
                'id' => 342,
                'menu_id' => 90,
                'permission_id' => 112,
            ),
            208 => 
            array (
                'id' => 343,
                'menu_id' => 91,
                'permission_id' => 119,
            ),
            209 => 
            array (
                'id' => 344,
                'menu_id' => 88,
                'permission_id' => 299,
            ),
            210 => 
            array (
                'id' => 345,
                'menu_id' => 89,
                'permission_id' => 302,
            ),
            211 => 
            array (
                'id' => 346,
                'menu_id' => 92,
                'permission_id' => 126,
            ),
            212 => 
            array (
                'id' => 347,
                'menu_id' => 146,
                'permission_id' => 340,
            ),
            213 => 
            array (
                'id' => 348,
                'menu_id' => 161,
                'permission_id' => 167,
            ),
            214 => 
            array (
                'id' => 349,
                'menu_id' => 162,
                'permission_id' => 256,
            ),
            215 => 
            array (
                'id' => 350,
                'menu_id' => 105,
                'permission_id' => 163,
            ),
            216 => 
            array (
                'id' => 351,
                'menu_id' => 146,
                'permission_id' => 325,
            ),
            217 => 
            array (
                'id' => 352,
                'menu_id' => 164,
                'permission_id' => 146,
            ),
            218 => 
            array (
                'id' => 353,
                'menu_id' => 164,
                'permission_id' => 149,
            ),
            219 => 
            array (
                'id' => 355,
                'menu_id' => 166,
                'permission_id' => 308,
            ),
            220 => 
            array (
                'id' => 356,
                'menu_id' => 167,
                'permission_id' => 307,
            ),
            221 => 
            array (
                'id' => 357,
                'menu_id' => 166,
                'permission_id' => 309,
            ),
            222 => 
            array (
                'id' => 360,
                'menu_id' => 38,
                'permission_id' => 353,
            ),
            223 => 
            array (
                'id' => 361,
                'menu_id' => 142,
                'permission_id' => 315,
            ),
            224 => 
            array (
                'id' => 362,
                'menu_id' => 132,
                'permission_id' => 131,
            ),
            225 => 
            array (
                'id' => 363,
                'menu_id' => 173,
                'permission_id' => 344,
            ),
            226 => 
            array (
                'id' => 364,
                'menu_id' => 173,
                'permission_id' => 345,
            ),
            227 => 
            array (
                'id' => 365,
                'menu_id' => 173,
                'permission_id' => 346,
            ),
            228 => 
            array (
                'id' => 366,
                'menu_id' => 173,
                'permission_id' => 347,
            ),
            229 => 
            array (
                'id' => 367,
                'menu_id' => 173,
                'permission_id' => 348,
            ),
            230 => 
            array (
                'id' => 368,
                'menu_id' => 174,
                'permission_id' => 349,
            ),
            231 => 
            array (
                'id' => 369,
                'menu_id' => 174,
                'permission_id' => 350,
            ),
            232 => 
            array (
                'id' => 371,
                'menu_id' => 173,
                'permission_id' => 351,
            ),
            233 => 
            array (
                'id' => 373,
                'menu_id' => 175,
                'permission_id' => 98,
            ),
            234 => 
            array (
                'id' => 374,
                'menu_id' => 175,
                'permission_id' => 100,
            ),
            235 => 
            array (
                'id' => 375,
                'menu_id' => 175,
                'permission_id' => 101,
            ),
            236 => 
            array (
                'id' => 376,
                'menu_id' => 175,
                'permission_id' => 102,
            ),
            237 => 
            array (
                'id' => 377,
                'menu_id' => 132,
                'permission_id' => 132,
            ),
            238 => 
            array (
                'id' => 378,
                'menu_id' => 132,
                'permission_id' => 134,
            ),
            239 => 
            array (
                'id' => 379,
                'menu_id' => 132,
                'permission_id' => 133,
            ),
            240 => 
            array (
                'id' => 380,
                'menu_id' => 136,
                'permission_id' => 354,
            ),
            241 => 
            array (
                'id' => 381,
                'menu_id' => 136,
                'permission_id' => 262,
            ),
            242 => 
            array (
                'id' => 382,
                'menu_id' => 136,
                'permission_id' => 264,
            ),
            243 => 
            array (
                'id' => 383,
                'menu_id' => 137,
                'permission_id' => 272,
            ),
            244 => 
            array (
                'id' => 384,
                'menu_id' => 137,
                'permission_id' => 274,
            ),
            245 => 
            array (
                'id' => 388,
                'menu_id' => 173,
                'permission_id' => 352,
            ),
            246 => 
            array (
                'id' => 389,
                'menu_id' => 177,
                'permission_id' => 357,
            ),
            247 => 
            array (
                'id' => 390,
                'menu_id' => 178,
                'permission_id' => 356,
            ),
            248 => 
            array (
                'id' => 391,
                'menu_id' => 179,
                'permission_id' => 358,
            ),
            249 => 
            array (
                'id' => 394,
                'menu_id' => 175,
                'permission_id' => 359,
            ),
            250 => 
            array (
                'id' => 395,
                'menu_id' => 164,
                'permission_id' => 150,
            ),
            251 => 
            array (
                'id' => 396,
                'menu_id' => 185,
                'permission_id' => 361,
            ),
            252 => 
            array (
                'id' => 397,
                'menu_id' => 189,
                'permission_id' => 362,
            ),
            253 => 
            array (
                'id' => 398,
                'menu_id' => 189,
                'permission_id' => 363,
            ),
            254 => 
            array (
                'id' => 399,
                'menu_id' => 188,
                'permission_id' => 364,
            ),
            255 => 
            array (
                'id' => 400,
                'menu_id' => 188,
                'permission_id' => 365,
            ),
            256 => 
            array (
                'id' => 401,
                'menu_id' => 186,
                'permission_id' => 366,
            ),
            257 => 
            array (
                'id' => 403,
                'menu_id' => 187,
                'permission_id' => 368,
            ),
            258 => 
            array (
                'id' => 405,
                'menu_id' => 187,
                'permission_id' => 369,
            ),
            259 => 
            array (
                'id' => 410,
                'menu_id' => 190,
                'permission_id' => 370,
            ),
            260 => 
            array (
                'id' => 411,
                'menu_id' => 190,
                'permission_id' => 371,
            ),
            261 => 
            array (
                'id' => 412,
                'menu_id' => 186,
                'permission_id' => 367,
            ),
            262 => 
            array (
                'id' => 414,
                'menu_id' => 189,
                'permission_id' => 372,
            ),
        ));
        
        
    }
}
