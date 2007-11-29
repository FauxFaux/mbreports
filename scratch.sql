-- BF's craziness:

select 
artist.id as artist_id, album.id as album_id, album.name as album_name, albumjoin.sequence as track_number, track.name as track_name, track.id as track_id
from 
artist,track,album,albumjoin
where
track.id = albumjoin.track AND
albumjoin.album = album.id AND
album.artist = artist.id AND
artist.name = 'Ennio Morricone'
order by album.name,track_number


-- String_accum:

CREATE FUNCTION concat(text, text) RETURNS text AS 'select $1||$2' LANGUAGE SQL IMMUTABLE RETURNS NULL ON NULL INPUT;
CREATE AGGREGATE string_accum (text) ( sfunc = concat, stype = text, initcond = '' );


create or replace temporary view at_noempties as select * from album_tracklist where sum_array(tracklist) != 0;
--select track_count,count(*) from at_noempties group by track_count order by count desc;
select * from at_noempties where track_count = 20 order by tracklist[1] asc;


CREATE OR REPLACE FUNCTION array_search(arr integer[], what integer)
  RETURNS boolean AS
$BODY$
	BEGIN
		FOR i IN ARRAY_LOWER(arr,1)..ARRAY_UPPER(arr,1) LOOP
			IF arr[i] =  what THEN
				RETURN true;
			END IF;
		END LOOP;
		RETURN false;
	END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;




-- Function: array_search(integer[], integer)

-- DROP FUNCTION array_search(integer[], integer);

CREATE OR REPLACE FUNCTION array_search(arr integer[], what integer)
  RETURNS boolean AS
$BODY$
	BEGIN
		FOR i IN ARRAY_LOWER(arr,1)..ARRAY_UPPER(arr,1) LOOP
			IF arr[i] =  what THEN
				RETURN true;
			END IF;
		END LOOP;
		RETURN false;
	END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION array_search(integer[], integer) OWNER TO postgres;



-- Function: concat(text, text)

-- DROP FUNCTION concat(text, text);

CREATE OR REPLACE FUNCTION concat(text, text)
  RETURNS text AS
'select $1||$2'
  LANGUAGE 'sql' IMMUTABLE STRICT;
ALTER FUNCTION concat(text, text) OWNER TO postgres;



-- Function: simple_cd_hash(integer[])

-- DROP FUNCTION simple_cd_hash(integer[]);

CREATE OR REPLACE FUNCTION simple_cd_hash(arr integer[])
  RETURNS bigint AS
$BODY$
	DECLARE
		ret BIGINT;
		upper INTEGER;
	BEGIN
		upper = ARRAY_UPPER(arr,1);
		IF (upper > 4) THEN
			upper = 4;
		END IF;
		ret = 0;
		FOR i IN ARRAY_LOWER(arr,1)..upper LOOP
			ret = ret + (arr[i]/1000) * 600^(i-1);
		END LOOP;
		RETURN ret;
	END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION simple_cd_hash(integer[]) OWNER TO postgres;



-- Function: sum_array(integer[])

-- DROP FUNCTION sum_array(integer[]);

CREATE OR REPLACE FUNCTION sum_array(arr integer[])
  RETURNS integer AS
$BODY$
	DECLARE
		ret INTEGER;
	BEGIN
		ret = 0;
		FOR i IN ARRAY_LOWER(arr,1)..ARRAY_UPPER(arr,1) LOOP
			ret = ret + arr[i];
		END LOOP;
		RETURN ret;
	END
$BODY$
  LANGUAGE 'plpgsql' VOLATILE;
ALTER FUNCTION sum_array(integer[]) OWNER TO postgres;
