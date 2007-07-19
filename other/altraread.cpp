#include <string>
#include <iostream>
#include <sstream>
#include <fstream>
#include <cstring>
#include <cmath>
#include <vector>
#include <algorithm>

using namespace std;

typedef vector<int> v_i;

ostream& operator<<(ostream& o, const v_i& tl)
{
	copy(tl.begin(), tl.end(), ostream_iterator<int>(o, ", "));
	return o;
}

struct Rec
{
	Rec(v_i list, long aid) : list(list), aid(aid) {}
	v_i list;
	long aid;
};

int operator<(const Rec& l, const Rec&r)
{
	return l.list < r.list;
}

bool operator==(const Rec& l, const Rec&r)
{
	return l.list == r.list;
}

template<typename T>
T sum(const vector<T>& thing)
{
	T res = T();
	for (vector<T>::const_iterator it = thing.begin(); it != thing.end(); ++it)
		res += *it;
	return res;
}

typedef vector<Rec> v_r;

int main()
{
	cerr << "Setting up.... " << flush;

	ifstream fi ("altraread.dat");
	string line;

	vector<Rec> vecs;
	vecs.reserve(400000);

	cerr << "done." << endl << "Reading file.. " << flush;

	while (getline(fi, line))
	{
		if (!isdigit(line[0]))
			continue;

		long id; string mid; int count;
		{
			stringstream ss; ss << line;
			if (!(ss >> id >> mid >> count))
				continue;
			if (count < 3)
				continue;
		}
		{
			v_i out(count);
			stringstream ss; ss << mid;
			char dis; ss >> dis;
			string ti;
			while (getline(ss, ti, ','))
				out.push_back(atoi(ti.c_str()));

			if (!sum(out))
				continue;

			vecs.push_back(Rec(out, id));
		}
	}

	cerr << "done." << endl << "Sorting....... " << flush;

	sort(vecs.begin(), vecs.end());

	cerr << "done." << endl;

	Rec first(v_i(), 0);
	Rec &last = first;
	for (v_r::iterator it = vecs.begin(); it != vecs.end(); ++it)
	{
		if (last == *it)
			cout << it->aid << '\t' << last.aid << endl;
		last = *it;
	}

	return 0;
}