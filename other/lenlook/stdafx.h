#pragma once

#include <boost/array.hpp>
#include <boost/static_assert.hpp>

#include <list>
#include <map>
#include <vector>
#include <iostream>
#include <cassert>

#include <pqxx/connection.hxx>
#include <pqxx/transaction.hxx>
#include <pqxx/cursor.hxx>

// No idea why this is required:
namespace pqxx
{
	template <>
	std::string to_string<unsigned __int64>(unsigned __int64 const & foo)
	{
		std::stringstream ss;
		ss << foo;
		std::string res;
		ss >> res;
		return res;
	}
}