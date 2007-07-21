#include "stdafx.h"

using namespace boost;
using namespace std;
using namespace pqxx;

typedef unsigned char tn_t;
typedef unsigned long len_t;

typedef unsigned long album_t;

typedef map<tn_t, len_t> lens_t;

boost::array<vector<int>, 99> t_m;

int main()
{
	try
	{
		connection conn("host=192.168.1.3 user=postgres dbname=musicbrainz_db");
		cout << "Conntected..\n";
		work T(conn, "readstuff");
		cout << "Transacted..\n";
		icursorstream ti(T, "select id,length from track", "one");
		cout << ".";
		icursorstream ai(T, "select track,album,\"sequence\" from albumjoin", "two");
		cout << "Queried..\n";
//		for (result::const_iterator ti = track.begin(), ai = ajoin.begin(); ti != track.end() && ai != ajoin.end(); ++ti, ++ai)
//			cout << ti[0] << ai[0] << endl;

		map<album_t, lens_t> allen;
		result tr, ar;
		size_t counter = 0;
		while (ti >> tr && ai >> ar)
		{
			if (++counter > 5)
			{
				counter = 0;
				cout << ".";
			}

			const result::tuple& tt = tr[0],
				at = ar[0];
			assert(at[0].as(0) == tt[0].as(0));
			allen[at[1].as(0)][at[2].as(0)] = tt[1].as(0);
		}
		cout << allen.size();
		return 0;
	}
	catch (std::exception &e)
	{
		cout << "Exception: " << e.what() << endl;
	}
}

